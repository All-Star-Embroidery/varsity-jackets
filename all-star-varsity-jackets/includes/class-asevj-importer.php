<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ASEVJ_Importer {
    private static ?ASEVJ_Importer $instance = null;

    public static function instance(): ASEVJ_Importer {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_post_asevj_import_legacy_zip', [ $this, 'handle_import' ] );
    }

    public static function render_importer(): void {
        $result = get_transient( 'asevj_import_result_' . get_current_user_id() );
        if ( $result ) {
            delete_transient( 'asevj_import_result_' . get_current_user_id() );
            $class = empty( $result['errors'] ) ? 'is-good' : '';
            echo '<div class="asevj-status-card ' . esc_attr( $class ) . '"><strong>' . esc_html( $result['headline'] ) . '</strong><span>' . esc_html( $result['summary'] ) . '</span>';
            if ( ! empty( $result['school_id'] ) ) {
                $organize = add_query_arg( [ 'page' => 'asevj-organizer', 'school_id' => absint( $result['school_id'] ) ], admin_url( 'admin.php' ) );
                echo '<a class="button button-primary" href="' . esc_url( $organize ) . '">Organize Imported Styles</a>';
            }
            echo '</div>';
            if ( ! empty( $result['errors'] ) ) {
                echo '<details class="asevj-import-errors"><summary>Show import warnings (' . esc_html( count( $result['errors'] ) ) . ')</summary><ul>';
                foreach ( array_slice( $result['errors'], 0, 40 ) as $error ) {
                    echo '<li>' . esc_html( $error ) . '</li>';
                }
                echo '</ul></details>';
            }
        }

        echo '<section class="asevj-admin-card asevj-import-card">';
        echo '<div class="asevj-import-heading"><div><span class="asevj-badge is-gold">School ZIP importer</span><h2>Import schools, branding & jacket photography</h2><p>Upload one ZIP containing a school CSV plus one folder per school. Schools may contain images directly or nested style folders such as <strong>Style 1 (Maroon)</strong> and <strong>Style 2 (Black)</strong>. The importer creates each style separately, stores the school logo and optional mascot artwork once, uses <strong>Front</strong> as each style’s main jacket image, and orders <strong>Back → Letter → Sleeve → Detail</strong> in each gallery.</p></div></div>';
        echo '<div class="asevj-import-flow">';
        echo '<div><b>1</b><span><strong>Choose the ZIP</strong><small>Include <code>schools.csv</code> and a folder for each school.</small></span></div>';
        echo '<div><b>2</b><span><strong>Schools & styles are matched</strong><small>CSV rows match School Name; nested style folders become separate jacket styles.</small></span></div>';
        echo '<div><b>3</b><span><strong>Images land correctly</strong><small>Logo → school logo, Mascot → mascot artwork, Front → featured jacket, Back/Letter/Sleeve/Detail → gallery.</small></span></div>';
        echo '</div>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="asevj-import-form">';
        wp_nonce_field( 'asevj_import_legacy_zip', 'asevj_import_nonce' );
        echo '<input type="hidden" name="action" value="asevj_import_legacy_zip">';
        echo '<label class="asevj-file-drop"><span class="dashicons dashicons-upload"></span><strong>Select your varsity jacket ZIP</strong><small>Maximum upload size: ' . esc_html( size_format( wp_max_upload_size() ) ) . '</small><input type="file" name="asevj_legacy_zip" accept=".zip,application/zip" required></label>';
        echo '<div class="asevj-import-safety"><strong>Expected filenames:</strong> <code>{School Name} Logo</code>, <code>{School Name} Mascot</code>, and jacket images ending in <code>Front</code>, <code>Back</code>, <code>Letter</code>, <code>Sleeve</code>, or <code>Detail</code>. Extra style words are okay (for example <code>John Glenn Maroon Front</code>). Nested folders such as <code>John Glenn/Style 1 (Maroon)/</code> are supported. JPG/JPEG/PNG/WEBP/GIF/AVIF are accepted. Logo and Mascot are optional. <strong>Safe to rerun:</strong> imported media are tagged by ZIP path and reused.</div>';
        echo '<label class="asevj-field" style="max-width:440px"><strong>If a school already exists</strong><select name="asevj_existing_mode"><option value="update">Update it with the CSV/images</option><option value="skip">Skip that school</option></select></label>';
        echo '<div class="asevj-import-selected" aria-live="polite"></div>';
        submit_button( 'Import Schools & Jacket Images', 'primary button-hero', 'submit', false );
        echo '<div class="asevj-import-working" hidden><span class="spinner is-active"></span><strong>Uploading & importing…</strong><small>Keep this tab open until WordPress returns the import summary.</small></div>';
        echo '</form>';
        echo '</section>';
        echo '<section class="asevj-admin-card asevj-import-card"><h2>CSV format</h2><p><strong>Required:</strong> <code>School Name</code>. Optional columns are <code>Mascot</code>, <code>Location</code>, <code>District</code>, <code>Description</code>, <code>Primary Color</code>, <code>Secondary Color</code>, <code>Accent Color</code>, <code>Style Name</code>, <code>Style Subtitle</code>, <code>Style Description</code>, <code>Features</code>, <code>Price</code>, and <code>CTA</code>. The <code>Mascot</code> CSV column is the mascot <em>name</em>; mascot artwork is detected from the <code>{School Name} Mascot</code> image file. Extra columns are ignored, so the sheet can grow later without breaking imports. If Style Subtitle, Style Description, or Features are blank, the importer creates safe defaults from the school/style names and the image roles actually present.</p><p class="description">For backwards compatibility, a legacy ZIP without <code>schools.csv</code> still falls back to the original image-manifest importer.</p></section>';
    }

    public function handle_import(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to import varsity jacket data.' );
        }
        check_admin_referer( 'asevj_import_legacy_zip', 'asevj_import_nonce' );

        $redirect = admin_url( 'admin.php?page=asevj-import' );
        $errors = [];

        if ( empty( $_FILES['asevj_legacy_zip']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['asevj_legacy_zip']['error'] ) {
            $this->save_result( 'Import could not start', 'No valid ZIP upload was received.', [ 'Please choose the ZIP file again.' ] );
            wp_safe_redirect( $redirect );
            exit;
        }
        if ( ! class_exists( 'ZipArchive' ) ) {
            $this->save_result( 'ZIP support is unavailable', 'This server does not have PHP ZipArchive enabled.', [ 'Ask the host to enable the PHP zip extension.' ] );
            wp_safe_redirect( $redirect );
            exit;
        }

        $tmp_zip = $_FILES['asevj_legacy_zip']['tmp_name'];
        $zip = new ZipArchive();
        if ( true !== $zip->open( $tmp_zip ) ) {
            $this->save_result( 'Could not open ZIP', 'The uploaded file could not be read as a ZIP archive.', [] );
            wp_safe_redirect( $redirect );
            exit;
        }

        $school_rows = $this->read_school_csv( $zip );
        if ( $school_rows ) {
            $this->handle_structured_import( $zip, $school_rows, $redirect );
        }

        $manifest = $this->read_manifest( $zip );
        $entries = $this->collect_image_entries( $zip );
        if ( ! $entries ) {
            $zip->close();
            $this->save_result( 'No jacket images found', 'The ZIP opened successfully, but no JPG, PNG, WEBP, GIF, or AVIF images were found inside school folders.', [] );
            wp_safe_redirect( $redirect );
            exit;
        }

        $school_level = $this->detect_school_segment( $entries );
        $groups = [];
        foreach ( $entries as $entry ) {
            if ( empty( $entry['parts'][ $school_level ] ) ) {
                continue;
            }
            $school_name = $this->pretty_school_name( $entry['parts'][ $school_level ] );
            if ( '' === $school_name ) {
                continue;
            }
            $groups[ $school_name ][] = $entry;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $schools_created = 0;
        $schools_matched = 0;
        $styles_created = 0;
        $images_created = 0;
        $images_reused = 0;
        $first_school_id = 0;

        foreach ( $groups as $school_name => $school_entries ) {
            $school_id = $this->find_school( $school_name );
            if ( $school_id ) {
                $schools_matched++;
            } else {
                $school_id = wp_insert_post( [
                    'post_type'   => 'asevj_school',
                    'post_status' => 'publish',
                    'post_title'  => $school_name,
                    'post_name'   => sanitize_title( $school_name ),
                ], true );
                if ( is_wp_error( $school_id ) ) {
                    $errors[] = $school_name . ': could not create school (' . $school_id->get_error_message() . ').';
                    continue;
                }
                update_post_meta( $school_id, '_asevj_enabled', 1 );
                $schools_created++;
            }
            if ( ! $first_school_id ) {
                $first_school_id = (int) $school_id;
            }

            $style_id = $this->find_import_style( (int) $school_id );
            if ( ! $style_id ) {
                $style_id = wp_insert_post( [
                    'post_type'   => 'asevj_style',
                    'post_status' => 'publish',
                    'post_title'  => 'Imported Jacket Gallery',
                    'menu_order'  => 0,
                ], true );
                if ( is_wp_error( $style_id ) ) {
                    $errors[] = $school_name . ': could not create the imported jacket style (' . $style_id->get_error_message() . ').';
                    continue;
                }
                update_post_meta( $style_id, '_asevj_school_id', (int) $school_id );
                update_post_meta( $style_id, '_asevj_enabled', 1 );
                update_post_meta( $style_id, '_asevj_imported_legacy', 1 );
                update_post_meta( $style_id, '_asevj_subtitle', 'Original varsity jacket examples' );
                update_post_meta( $style_id, '_asevj_description', 'Original jacket examples imported from the previous All Star Embroidery varsity gallery.' );
                update_post_meta( $style_id, '_asevj_cta', 'View Jacket Details' );
                $styles_created++;
            }

            $attachment_ids = [];
            foreach ( $school_entries as $entry ) {
                $source_path = $entry['name'];
                $existing = get_posts( [
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'meta_key'       => '_asevj_legacy_source_path',
                    'meta_value'     => $source_path,
                ] );
                if ( $existing ) {
                    $attachment_ids[] = (int) $existing[0];
                    $images_reused++;
                    continue;
                }

                $contents = $zip->getFromName( $entry['name'] );
                if ( false === $contents ) {
                    $errors[] = $school_name . ': could not read ' . basename( $entry['name'] ) . ' from the ZIP.';
                    continue;
                }

                $filename = sanitize_file_name( basename( $entry['name'] ) );
                $temp = wp_tempnam( $filename );
                if ( ! $temp || false === file_put_contents( $temp, $contents ) ) {
                    $errors[] = $school_name . ': could not create a temporary file for ' . $filename . '.';
                    if ( $temp ) {
                        @unlink( $temp );
                    }
                    continue;
                }

                $description = $this->manifest_description( $manifest, $school_name, $filename );
                $file_array = [ 'name' => $filename, 'tmp_name' => $temp ];
                $attachment_id = media_handle_sideload( $file_array, (int) $style_id, $description ?: pathinfo( $filename, PATHINFO_FILENAME ) );
                if ( is_wp_error( $attachment_id ) ) {
                    @unlink( $temp );
                    $errors[] = $school_name . ': ' . $filename . ' — ' . $attachment_id->get_error_message();
                    continue;
                }

                update_post_meta( $attachment_id, '_asevj_legacy_source_path', $source_path );
                update_post_meta( $attachment_id, '_asevj_legacy_school', $school_name );
                if ( $description ) {
                    wp_update_post( [
                        'ID'           => $attachment_id,
                        'post_excerpt' => $description,
                    ] );
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $description );
                }
                $attachment_ids[] = (int) $attachment_id;
                $images_created++;
            }

            $attachment_ids = array_values( array_unique( array_filter( array_map( 'absint', $attachment_ids ) ) ) );
            if ( $attachment_ids ) {
                $featured_id = get_post_thumbnail_id( (int) $style_id );
                if ( ! $featured_id ) {
                    $featured_id = array_shift( $attachment_ids );
                    set_post_thumbnail( (int) $style_id, (int) $featured_id );
                } else {
                    $attachment_ids = array_values( array_diff( $attachment_ids, [ (int) $featured_id ] ) );
                }

                $existing_gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( (int) $style_id, '_asevj_gallery_ids', true ) ) ) );
                $gallery_ids = array_values( array_unique( array_merge( $existing_gallery, $attachment_ids ) ) );
                update_post_meta( (int) $style_id, '_asevj_gallery_ids', implode( ',', $gallery_ids ) );
            }
        }

        $zip->close();

        $headline = 'Legacy varsity import complete';
        $summary = sprintf(
            '%d school(s) created, %d matched, %d initial style(s) created, %d image(s) imported, %d existing image(s) reused.',
            $schools_created,
            $schools_matched,
            $styles_created,
            $images_created,
            $images_reused
        );
        $this->save_result( $headline, $summary, $errors, $first_school_id );
        wp_safe_redirect( $redirect );
        exit;
    }


    /**
     * Read the structured school CSV. The importer intentionally accepts a
     * few friendly header aliases so the spreadsheet is easy to maintain.
     */
    private function read_school_csv( ZipArchive $zip ): array {
        $csv_name = '';
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = (string) $zip->getNameIndex( $i );
            $base = strtolower( basename( $name ) );
            if ( in_array( $base, [ 'schools.csv', 'school-info.csv', 'school_info.csv', 'varsity-schools.csv' ], true ) ) {
                $csv_name = $name;
                break;
            }
        }
        if ( ! $csv_name ) {
            return [];
        }

        $csv = $zip->getFromName( $csv_name );
        if ( false === $csv || '' === trim( (string) $csv ) ) {
            return [];
        }

        $handle = fopen( 'php://temp', 'r+' );
        fwrite( $handle, (string) $csv );
        rewind( $handle );
        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle );
            return [];
        }

        $keys = [];
        foreach ( $header as $index => $value ) {
            $keys[ $index ] = $this->csv_header_key( (string) $value );
        }

        $rows = [];
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $item = [];
            foreach ( $keys as $index => $key ) {
                if ( '' !== $key && isset( $row[ $index ] ) ) {
                    $item[ $key ] = trim( (string) $row[ $index ] );
                }
            }
            if ( ! empty( $item['school_name'] ) ) {
                $rows[] = $item;
            }
        }
        fclose( $handle );
        return $rows;
    }

    private function csv_header_key( string $header ): string {
        // Excel/Windows CSV exports commonly include a UTF-8 BOM on the first
        // header. Without stripping it, "School Name" is not recognized and
        // the importer can incorrectly fall back to the legacy image importer.
        $header = preg_replace( '/^\xEF\xBB\xBF/', '', $header );
        $header = strtolower( trim( preg_replace( '/\s+/', ' ', str_replace( [ '_', '-' ], ' ', $header ) ) ) );
        $aliases = [
            'school' => 'school_name', 'school name' => 'school_name', 'name' => 'school_name',
            'mascot' => 'mascot',
            'location' => 'location', 'city' => 'location',
            'district' => 'district', 'district group' => 'district', 'group' => 'district',
            'description' => 'description', 'school description' => 'description',
            'primary' => 'primary', 'primary color' => 'primary', 'primary colour' => 'primary',
            'secondary' => 'secondary', 'secondary color' => 'secondary', 'secondary colour' => 'secondary',
            'accent' => 'accent', 'accent color' => 'accent', 'accent colour' => 'accent',
            'style' => 'style_name', 'style name' => 'style_name',
            'style subtitle' => 'style_subtitle', 'subtitle' => 'style_subtitle',
            'style description' => 'style_description', 'style details' => 'style_description',
            'features' => 'features', 'style features' => 'features', 'feature list' => 'features',
            'price' => 'price', 'fallback price' => 'price', 'starting price' => 'price',
            'cta' => 'cta', 'button text' => 'cta', 'cta text' => 'cta',
        ];
        return $aliases[ $header ] ?? '';
    }

    private function handle_structured_import( ZipArchive $zip, array $rows, string $redirect ): void {
        $entries = $this->collect_image_entries( $zip );
        $school_level = $this->detect_school_segment( $entries );
        $groups = $this->group_images_by_school_folder( $entries );
        $mode = isset( $_POST['asevj_existing_mode'] ) && 'skip' === sanitize_key( (string) $_POST['asevj_existing_mode'] ) ? 'skip' : 'update';

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $styles_created = 0;
        $images_created = 0;
        $images_reused = 0;
        $errors = [];
        $first_school_id = 0;

        foreach ( $rows as $row ) {
            $school_name = sanitize_text_field( (string) $row['school_name'] );
            if ( '' === $school_name ) {
                continue;
            }

            $school_id = $this->find_school( $school_name );
            if ( $school_id && 'skip' === $mode ) {
                $skipped++;
                continue;
            }

            if ( ! $school_id ) {
                $school_id = wp_insert_post( [
                    'post_type'   => 'asevj_school',
                    'post_status' => 'publish',
                    'post_title'  => $school_name,
                    'post_name'   => sanitize_title( $school_name ),
                ], true );
                if ( is_wp_error( $school_id ) ) {
                    $errors[] = $school_name . ': could not create school (' . $school_id->get_error_message() . ').';
                    continue;
                }
                update_post_meta( $school_id, '_asevj_enabled', 1 );
                $created++;
            } else {
                $updated++;
            }

            if ( ! $first_school_id ) {
                $first_school_id = (int) $school_id;
            }

            $this->update_school_from_csv( (int) $school_id, $row );

            $key = $this->normalize_school_key( $school_name );
            $school_entries = $groups[ $key ] ?? [];
            if ( ! $school_entries ) {
                foreach ( $entries as $entry ) {
                    if ( $this->entry_matches_school( $entry, $school_name, $school_level ) ) {
                        $school_entries[] = $entry;
                    }
                }
            }

            if ( ! $school_entries ) {
                $errors[] = $school_name . ': no school image folder was found in this ZIP.';
                continue;
            }

            // School-level branding. Prefer shallower copies if duplicates exist.
            $branding_entries = $school_entries;
            usort( $branding_entries, static fn( $a, $b ) => count( $a['parts'] ) <=> count( $b['parts'] ) );
            $branding = [];
            foreach ( $branding_entries as $entry ) {
                $role = $this->image_role( basename( $entry['name'] ) );
                if ( in_array( $role, [ 'logo', 'mascot' ], true ) && ! isset( $branding[ $role ] ) ) {
                    $branding[ $role ] = $entry;
                }
            }
            foreach ( [ 'logo', 'mascot' ] as $role ) {
                if ( empty( $branding[ $role ] ) ) {
                    continue;
                }
                $attachment = $this->import_structured_image( $zip, $branding[ $role ], $school_name, $role, (int) $school_id, $images_created, $images_reused, $errors );
                if ( $attachment ) {
                    update_post_meta( (int) $school_id, 'logo' === $role ? '_asevj_logo_id' : '_asevj_mascot_image_id', (int) $attachment );
                }
            }

            $style_groups = $this->group_school_entries_by_style( $school_entries, $school_level, $school_name, $row );
            if ( ! $style_groups ) {
                $errors[] = $school_name . ': no jacket images were matched to a style.';
                continue;
            }

            foreach ( $style_groups as $style_group ) {
                $style_name = sanitize_text_field( (string) $style_group['name'] );
                $style_key = sanitize_key( (string) $style_group['key'] );
                $menu_order = max( 0, (int) $style_group['order'] );
                $style_entries = (array) $style_group['entries'];

                $style_id = $this->find_structured_style( (int) $school_id, $style_name, $style_key );
                if ( ! $style_id ) {
                    $style_id = wp_insert_post( [
                        'post_type'   => 'asevj_style',
                        'post_status' => 'publish',
                        'post_title'  => $style_name,
                        'menu_order'  => $menu_order,
                    ], true );
                    if ( is_wp_error( $style_id ) ) {
                        $errors[] = $school_name . ' / ' . $style_name . ': could not create jacket style (' . $style_id->get_error_message() . ').';
                        continue;
                    }
                    update_post_meta( $style_id, '_asevj_school_id', (int) $school_id );
                    update_post_meta( $style_id, '_asevj_enabled', 1 );
                    update_post_meta( $style_id, '_asevj_imported_structured', 1 );
                    $styles_created++;
                }

                wp_update_post( [
                    'ID'         => (int) $style_id,
                    'post_title' => $style_name,
                    'menu_order' => $menu_order,
                ] );
                update_post_meta( (int) $style_id, '_asevj_import_style_key', $style_key );
                $this->update_style_from_csv( (int) $style_id, $row );

                $role_entries = [ 'front' => [], 'back' => [], 'letter' => [], 'sleeve' => [], 'detail' => [] ];
                foreach ( $style_entries as $entry ) {
                    $role = $this->image_role( basename( $entry['name'] ) );
                    if ( isset( $role_entries[ $role ] ) ) {
                        $role_entries[ $role ][] = $entry;
                    }
                }

                if ( empty( $role_entries['front'] ) ) {
                    $errors[] = $school_name . ' / ' . $style_name . ': no Front image was found.';
                }

                // Fill the customer-facing style fields when the CSV did not
                // provide them. These defaults are derived only from the school,
                // style folder/name, and image roles actually present in the ZIP.
                $this->populate_style_defaults_from_import(
                    (int) $style_id,
                    $school_name,
                    $style_name,
                    $role_entries
                );

                $attachment_by_role = [];
                foreach ( [ 'front', 'back', 'letter', 'sleeve' ] as $role ) {
                    if ( empty( $role_entries[ $role ] ) ) {
                        continue;
                    }
                    if ( count( $role_entries[ $role ] ) > 1 ) {
                        $errors[] = $school_name . ' / ' . $style_name . ': more than one ' . ucfirst( $role ) . ' image was found; the first one was used.';
                    }
                    $attachment = $this->import_structured_image( $zip, $role_entries[ $role ][0], $school_name, $role, (int) $style_id, $images_created, $images_reused, $errors );
                    if ( $attachment ) {
                        $attachment_by_role[ $role ] = [ (int) $attachment ];
                    }
                }

                // Detail may intentionally contain more than one close-up. Keep all of them.
                foreach ( $role_entries['detail'] as $detail_entry ) {
                    $attachment = $this->import_structured_image( $zip, $detail_entry, $school_name, 'detail', (int) $style_id, $images_created, $images_reused, $errors );
                    if ( $attachment ) {
                        $attachment_by_role['detail'][] = (int) $attachment;
                    }
                }

                if ( ! empty( $attachment_by_role['front'][0] ) ) {
                    set_post_thumbnail( (int) $style_id, (int) $attachment_by_role['front'][0] );
                }

                $gallery = [];
                foreach ( [ 'back', 'letter', 'sleeve', 'detail' ] as $role ) {
                    if ( ! empty( $attachment_by_role[ $role ] ) ) {
                        foreach ( $attachment_by_role[ $role ] as $attachment_id ) {
                            $gallery[] = (int) $attachment_id;
                        }
                    }
                }
                update_post_meta( (int) $style_id, '_asevj_gallery_ids', implode( ',', array_values( array_unique( $gallery ) ) ) );
            }
        }

        $zip->close();
        $summary = sprintf(
            '%d school(s) created, %d updated, %d skipped, %d style(s) created, %d image(s) imported, %d media item(s) reused.',
            $created,
            $updated,
            $skipped,
            $styles_created,
            $images_created,
            $images_reused
        );
        $this->save_result( 'Structured school import complete', $summary, $errors, $first_school_id );
        wp_safe_redirect( $redirect );
        exit;
    }


    private function populate_style_defaults_from_import( int $style_id, string $school_name, string $style_name, array $role_entries ): void {
        if ( ! get_post_meta( $style_id, '_asevj_subtitle', true ) ) {
            $subtitle = 0 === strcasecmp( $style_name, 'Classic Varsity Jacket' )
                ? $school_name . ' varsity jacket'
                : $school_name . ' — ' . $style_name;
            update_post_meta( $style_id, '_asevj_subtitle', sanitize_text_field( $subtitle ) );
        }

        if ( ! get_post_meta( $style_id, '_asevj_description', true ) ) {
            $view_labels = [];
            foreach ( [
                'front'  => 'front',
                'back'   => 'back',
                'letter' => 'lettering',
                'sleeve' => 'sleeve',
                'detail' => 'detail',
            ] as $role => $label ) {
                if ( ! empty( $role_entries[ $role ] ) ) {
                    $view_labels[] = $label;
                }
            }

            $description = 'A school-specific ' . $school_name . ' varsity jacket style.';
            if ( $view_labels ) {
                $description .= ' Available photography includes ' . implode( ', ', $view_labels ) . ' views.';
            }
            update_post_meta( $style_id, '_asevj_description', sanitize_textarea_field( $description ) );
        }

        if ( ! get_post_meta( $style_id, '_asevj_features', true ) ) {
            $features = [ 'School-specific varsity jacket design' ];
            if ( ! empty( $role_entries['back'] ) ) {
                $features[] = 'Back decoration';
            }
            if ( ! empty( $role_entries['letter'] ) ) {
                $features[] = 'Custom lettering detail';
            }
            if ( ! empty( $role_entries['sleeve'] ) ) {
                $features[] = 'Sleeve decoration';
            }
            if ( ! empty( $role_entries['detail'] ) ) {
                $features[] = 'Custom detail work';
            }
            update_post_meta( $style_id, '_asevj_features', implode( "\n", $features ) );
        }

        if ( ! get_post_meta( $style_id, '_asevj_cta', true ) ) {
            update_post_meta( $style_id, '_asevj_cta', 'Customize This Jacket' );
        }
    }

    private function update_school_from_csv( int $school_id, array $row ): void {
        $map = [
            'mascot' => '_asevj_mascot',
            'location' => '_asevj_location',
            'district' => '_asevj_district',
            'description' => '_asevj_description',
        ];
        foreach ( $map as $key => $meta ) {
            if ( isset( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
                $value = 'description' === $key ? sanitize_textarea_field( (string) $row[ $key ] ) : sanitize_text_field( (string) $row[ $key ] );
                update_post_meta( $school_id, $meta, $value );
            }
        }
        foreach ( [ 'primary' => '_asevj_primary', 'secondary' => '_asevj_secondary', 'accent' => '_asevj_accent' ] as $key => $meta ) {
            if ( ! empty( $row[ $key ] ) ) {
                $color = sanitize_hex_color( (string) $row[ $key ] );
                if ( $color ) {
                    update_post_meta( $school_id, $meta, $color );
                }
            }
        }
    }

    private function update_style_from_csv( int $style_id, array $row ): void {
        if ( ! empty( $row['style_subtitle'] ) ) {
            update_post_meta( $style_id, '_asevj_subtitle', sanitize_text_field( (string) $row['style_subtitle'] ) );
        }
        if ( ! empty( $row['style_description'] ) ) {
            update_post_meta( $style_id, '_asevj_description', sanitize_textarea_field( (string) $row['style_description'] ) );
        }
        if ( ! empty( $row['features'] ) ) {
            $features = str_replace( [ "\r\n", "\r", '|' ], "\n", (string) $row['features'] );
            update_post_meta( $style_id, '_asevj_features', sanitize_textarea_field( $features ) );
        }
        if ( isset( $row['price'] ) && '' !== trim( (string) $row['price'] ) ) {
            $price = preg_replace( '/[^0-9.]/', '', (string) $row['price'] );
            if ( '' !== $price ) {
                update_post_meta( $style_id, '_asevj_fallback_price', $price );
            }
        }
        if ( ! empty( $row['cta'] ) ) {
            update_post_meta( $style_id, '_asevj_cta', sanitize_text_field( (string) $row['cta'] ) );
        } elseif ( ! get_post_meta( $style_id, '_asevj_cta', true ) ) {
            update_post_meta( $style_id, '_asevj_cta', 'Customize This Jacket' );
        }
    }

    private function group_images_by_school_folder( array $entries ): array {
        $groups = [];
        $level = $this->detect_school_segment( $entries );
        foreach ( $entries as $entry ) {
            $folder = $entry['parts'][ $level ] ?? '';
            if ( '' === $folder ) {
                continue;
            }
            $groups[ $this->normalize_school_key( $folder ) ][] = $entry;
        }
        return $groups;
    }

    private function normalize_school_key( string $value ): string {
        $value = strtolower( rawurldecode( $value ) );
        $value = preg_replace( '/[^a-z0-9]+/', '', $value );
        return (string) $value;
    }

    private function entry_matches_school( array $entry, string $school_name, int $school_level ): bool {
        $folder = (string) ( $entry['parts'][ $school_level ] ?? '' );
        if ( $folder && $this->normalize_school_key( $folder ) === $this->normalize_school_key( $school_name ) ) {
            return true;
        }
        return $this->filename_matches_school( basename( (string) $entry['name'] ), $school_name );
    }

    private function filename_matches_school( string $filename, string $school_name ): bool {
        $stem = trim( (string) preg_replace( '/[_-]+/', ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) );
        $stem = preg_replace( '/\s+/', ' ', $stem );
        if ( 0 === stripos( $stem, $school_name ) ) {
            return true;
        }
        return false;
    }

    /**
     * Supports the clean naming convention plus a few real-world legacy variants
     * already present in the All Star archive (Backpng, Detial, Front Jacket,
     * Front Logo, Letters, and semantic detail filenames).
     */
    private function image_role( string $filename ): string {
        $stem = strtolower( trim( (string) preg_replace( '/\s+/', ' ', preg_replace( '/[_-]+/', ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) ) ) );

        if ( preg_match( '/(?:^|\s)front(?:\s+(?:jacket|logo))?\s*$/i', $stem ) ) {
            return 'front';
        }
        if ( preg_match( '/(?:^|\s)(?:back|backpng)(?:\s+jacket)?\s*$/i', $stem ) ) {
            return 'back';
        }
        if ( str_contains( $stem, 'letter' ) && ( str_ends_with( $stem, 'detail' ) || preg_match( '/(?:^|\s)letters?\s*$/i', $stem ) ) ) {
            return 'letter';
        }
        if ( str_contains( $stem, 'sleeve' ) && ( str_ends_with( $stem, 'detail' ) || preg_match( '/(?:^|\s)sleeve\s*$/i', $stem ) ) ) {
            return 'sleeve';
        }
        if ( preg_match( '/(?:^|\s)letters?\s*$/i', $stem ) ) {
            return 'letter';
        }
        if ( preg_match( '/(?:^|\s)sleeve\s*$/i', $stem ) ) {
            return 'sleeve';
        }
        if ( preg_match( '/(?:^|\s)(?:detail|detial)\s*$/i', $stem ) ) {
            return 'detail';
        }
        if ( preg_match( '/(?:^|\s)mascot(?:png)?(?:\s*\(old\))?\s*$/i', $stem ) ) {
            return 'mascot';
        }
        if ( preg_match( '/(?:^|\s)logo\s*$/i', $stem ) ) {
            return 'logo';
        }
        return '';
    }

    private function group_school_entries_by_style( array $school_entries, int $school_level, string $school_name, array $row ): array {
        $nested = [];
        $flat = [];

        foreach ( $school_entries as $entry ) {
            $role = $this->image_role( basename( $entry['name'] ) );
            if ( in_array( $role, [ 'logo', 'mascot' ], true ) || '' === $role ) {
                continue;
            }
            $parts = $entry['parts'];
            if ( count( $parts ) > ( $school_level + 2 ) ) {
                $folder = (string) ( $parts[ $school_level + 1 ] ?? '' );
                $nested[ $folder ][] = $entry;
            } else {
                $flat[] = $entry;
            }
        }

        $result = [];
        $fallback_order = 0;
        foreach ( $nested as $folder => $style_entries ) {
            [ $order, $name ] = $this->style_from_folder( $school_name, $folder, $fallback_order );
            $result[] = [
                'key'     => 'folder-' . sanitize_title( $folder ),
                'name'    => $name,
                'order'   => $order,
                'entries' => $style_entries,
            ];
            $fallback_order++;
        }

        if ( $flat ) {
            $flat_groups = [];
            foreach ( $flat as $entry ) {
                $role = $this->image_role( basename( $entry['name'] ) );
                $descriptor = $this->file_style_descriptor( $school_name, basename( $entry['name'] ), $role );
                $key = $descriptor ?: '__default__';
                $flat_groups[ $key ][] = $entry;
            }

            // If flat files have several named Front images, they are separate styles
            // (for example West Muskingham Blue Front and Yellow Front).
            $named_fronts = [];
            foreach ( $flat_groups as $descriptor => $style_entries ) {
                if ( '__default__' === $descriptor ) {
                    continue;
                }
                foreach ( $style_entries as $entry ) {
                    if ( 'front' === $this->image_role( basename( $entry['name'] ) ) ) {
                        $named_fronts[] = $descriptor;
                        break;
                    }
                }
            }

            $split_flat = count( array_unique( $named_fronts ) ) > 1 || ( ! $nested && count( array_unique( $named_fronts ) ) > 0 && count( $flat_groups ) > 1 );
            if ( $split_flat ) {
                foreach ( $flat_groups as $descriptor => $style_entries ) {
                    if ( '__default__' === $descriptor ) {
                        continue;
                    }
                    $result[] = [
                        'key'     => 'flat-' . sanitize_title( $descriptor ),
                        'name'    => sanitize_text_field( $descriptor ),
                        'order'   => count( $result ),
                        'entries' => $style_entries,
                    ];
                }
            } elseif ( ! $nested || isset( $flat_groups['__default__'] ) ) {
                $style_name = sanitize_text_field( (string) ( $row['style_name'] ?? '' ) );
                if ( '' === $style_name ) {
                    $style_name = 'Classic Varsity Jacket';
                }
                $combined = [];
                foreach ( $flat_groups as $style_entries ) {
                    $combined = array_merge( $combined, $style_entries );
                }
                $result[] = [
                    'key'     => 'flat-default',
                    'name'    => $style_name,
                    'order'   => count( $result ),
                    'entries' => $combined,
                ];
            }
        }

        usort( $result, static fn( $a, $b ) => (int) $a['order'] <=> (int) $b['order'] );
        return $result;
    }

    private function style_from_folder( string $school_name, string $folder, int $fallback_order ): array {
        $folder = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[_-]+/', ' ', rawurldecode( $folder ) ) ) );
        if ( preg_match( '/^style\s*(\d+)\s*(?:\((.*?)\))?$/i', $folder, $match ) ) {
            $number = max( 1, (int) $match[1] );
            $name = ! empty( $match[2] ) ? trim( $match[2] ) : 'Style ' . $number;
            return [ $number - 1, $name ];
        }
        if ( preg_match( '/^' . preg_quote( $school_name, '/' ) . '\s*\((.*?)\)$/i', $folder, $match ) ) {
            return [ $fallback_order, trim( $match[1] ) ];
        }
        if ( preg_match( '/^' . preg_quote( $school_name, '/' ) . '\s+style\s*(\d+)$/i', $folder, $match ) ) {
            $number = max( 1, (int) $match[1] );
            return [ $number - 1, 'Style ' . $number ];
        }
        if ( preg_match( '/^(.*?)\s+style$/i', $folder, $match ) && trim( $match[1] ) ) {
            return [ $fallback_order, ucwords( trim( $match[1] ) ) ];
        }
        if ( 0 === stripos( $folder, $school_name . ' ' ) ) {
            return [ $fallback_order, trim( substr( $folder, strlen( $school_name ) ) ) ];
        }
        return [ $fallback_order, $folder ];
    }

    private function file_style_descriptor( string $school_name, string $filename, string $role ): string {
        $stem = trim( preg_replace( '/\s+/', ' ', preg_replace( '/[_-]+/', ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) ) );
        if ( 0 === stripos( $stem, $school_name ) ) {
            $stem = trim( substr( $stem, strlen( $school_name ) ) );
        }
        $patterns = [
            'front'  => [ '/\bfront\s+(?:jacket|logo)$/i', '/\bfront$/i' ],
            'back'   => [ '/\bback\s+jacket$/i', '/\bbackpng$/i', '/\bback$/i' ],
            'letter' => [ '/\bletters?$/i', '/\bletter.*detail$/i' ],
            'sleeve' => [ '/\bsleeve$/i', '/\bsleeve.*detail$/i' ],
            'detail' => [ '/\b(?:detail|detial)$/i' ],
        ];
        foreach ( $patterns[ $role ] ?? [] as $pattern ) {
            $stem = trim( (string) preg_replace( $pattern, '', $stem ) );
        }
        return sanitize_text_field( $stem );
    }

    private function find_structured_style( int $school_id, string $style_name, string $style_key = '' ): int {
        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => '_asevj_school_id',
            'meta_value'     => $school_id,
        ] );
        foreach ( $styles as $style_id ) {
            if ( $style_key && $style_key === (string) get_post_meta( (int) $style_id, '_asevj_import_style_key', true ) ) {
                return (int) $style_id;
            }
            if ( 0 === strcasecmp( trim( get_the_title( $style_id ) ), trim( $style_name ) ) ) {
                return (int) $style_id;
            }
        }
        if ( 1 === count( $styles ) && get_post_meta( (int) $styles[0], '_asevj_imported_legacy', true ) ) {
            wp_update_post( [ 'ID' => (int) $styles[0], 'post_title' => $style_name ] );
            update_post_meta( (int) $styles[0], '_asevj_imported_structured', 1 );
            update_post_meta( (int) $styles[0], '_asevj_import_style_key', $style_key );
            return (int) $styles[0];
        }
        return 0;
    }

    private function import_structured_image( ZipArchive $zip, array $entry, string $school_name, string $role, int $parent_id, int &$images_created, int &$images_reused, array &$errors ): int {
        $source_path = (string) $entry['name'];
        $existing = get_posts( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'OR',
                [ 'key' => '_asevj_import_source_path', 'value' => $source_path ],
                [ 'key' => '_asevj_legacy_source_path', 'value' => $source_path ],
            ],
        ] );
        if ( $existing ) {
            $images_reused++;
            return (int) $existing[0];
        }

        $contents = $zip->getFromName( $source_path );
        if ( false === $contents ) {
            $errors[] = $school_name . ': could not read ' . basename( $source_path ) . ' from the ZIP.';
            return 0;
        }

        $filename = sanitize_file_name( basename( $source_path ) );
        $temp = wp_tempnam( $filename );
        if ( ! $temp || false === file_put_contents( $temp, $contents ) ) {
            if ( $temp ) {
                @unlink( $temp );
            }
            $errors[] = $school_name . ': could not create a temporary file for ' . $filename . '.';
            return 0;
        }

        $label = $school_name . ' ' . ucfirst( $role );
        $attachment_id = media_handle_sideload( [ 'name' => $filename, 'tmp_name' => $temp ], $parent_id, $label );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $temp );
            $errors[] = $school_name . ': ' . $filename . ' — ' . $attachment_id->get_error_message();
            return 0;
        }

        update_post_meta( $attachment_id, '_asevj_import_source_path', $source_path );
        update_post_meta( $attachment_id, '_asevj_import_school', $school_name );
        update_post_meta( $attachment_id, '_asevj_import_role', $role );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $label );
        wp_update_post( [ 'ID' => $attachment_id, 'post_excerpt' => $label ] );
        $images_created++;
        return (int) $attachment_id;
    }

    private function read_manifest( ZipArchive $zip ): array {
        $manifest_name = '';
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = (string) $zip->getNameIndex( $i );
            if ( 'image-manifest.csv' === strtolower( basename( $name ) ) ) {
                $manifest_name = $name;
                break;
            }
        }
        if ( ! $manifest_name ) {
            return [];
        }
        $csv = $zip->getFromName( $manifest_name );
        if ( false === $csv || '' === trim( $csv ) ) {
            return [];
        }
        $handle = fopen( 'php://temp', 'r+' );
        fwrite( $handle, $csv );
        rewind( $handle );
        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle );
            return [];
        }
        $header = array_map( static fn( $v ) => strtolower( trim( (string) $v ) ), $header );
        $school_idx = array_search( 'school', $header, true );
        $file_idx = array_search( 'filename', $header, true );
        if ( false === $file_idx ) {
            $file_idx = array_search( 'file', $header, true );
        }
        $description_idx = array_search( 'description', $header, true );
        $map = [];
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( false === $school_idx || false === $file_idx ) {
                continue;
            }
            $school = isset( $row[ $school_idx ] ) ? trim( (string) $row[ $school_idx ] ) : '';
            $file = isset( $row[ $file_idx ] ) ? basename( trim( (string) $row[ $file_idx ] ) ) : '';
            $description = ( false !== $description_idx && isset( $row[ $description_idx ] ) ) ? trim( (string) $row[ $description_idx ] ) : '';
            if ( $school && $file ) {
                $map[ strtolower( $school . '|' . $file ) ] = $description;
            }
        }
        fclose( $handle );
        return $map;
    }

    private function collect_image_entries( ZipArchive $zip ): array {
        $entries = [];
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $stat = $zip->statIndex( $i );
            if ( ! $stat || empty( $stat['name'] ) ) {
                continue;
            }
            $name = str_replace( '\\', '/', (string) $stat['name'] );
            if ( str_ends_with( $name, '/' ) || str_contains( $name, '__MACOSX/' ) ) {
                continue;
            }
            $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
            if ( ! in_array( $ext, [ 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' ], true ) ) {
                continue;
            }
            $parts = array_values( array_filter( explode( '/', trim( $name, '/' ) ), static fn( $part ) => '' !== $part && ! str_starts_with( $part, '.' ) ) );
            if ( count( $parts ) < 2 ) {
                continue;
            }
            $entries[] = [ 'name' => $name, 'parts' => $parts ];
        }
        return $entries;
    }

    private function detect_school_segment( array $entries ): int {
        $first = [];
        $all_have_three = true;
        foreach ( $entries as $entry ) {
            $first[] = $entry['parts'][0] ?? '';
            if ( count( $entry['parts'] ) < 3 ) {
                $all_have_three = false;
            }
        }
        $first = array_values( array_unique( array_filter( $first ) ) );
        return ( 1 === count( $first ) && $all_have_three ) ? 1 : 0;
    }

    private function pretty_school_name( string $folder ): string {
        $folder = rawurldecode( $folder );
        $folder = preg_replace( '/[_-]+/', ' ', $folder );
        $folder = preg_replace( '/\s+/', ' ', (string) $folder );
        return trim( ucwords( strtolower( (string) $folder ) ) );
    }

    private function find_school( string $name ): int {
        $schools = get_posts( [
            'post_type'      => 'asevj_school',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );
        foreach ( $schools as $school_id ) {
            if ( 0 === strcasecmp( trim( get_the_title( $school_id ) ), trim( $name ) ) ) {
                return (int) $school_id;
            }
        }
        return 0;
    }

    private function find_import_style( int $school_id ): int {
        $styles = get_posts( [
            'post_type'      => 'asevj_style',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_asevj_school_id', 'value' => $school_id ],
                [ 'key' => '_asevj_imported_legacy', 'value' => 1 ],
            ],
        ] );
        return $styles ? (int) $styles[0] : 0;
    }

    private function manifest_description( array $manifest, string $school, string $filename ): string {
        $key = strtolower( $school . '|' . basename( $filename ) );
        return isset( $manifest[ $key ] ) ? sanitize_text_field( $manifest[ $key ] ) : '';
    }

    private function save_result( string $headline, string $summary, array $errors, int $school_id = 0 ): void {
        set_transient( 'asevj_import_result_' . get_current_user_id(), [
            'headline' => $headline,
            'summary'  => $summary,
            'errors'   => array_values( $errors ),
            'school_id' => $school_id,
        ], 10 * MINUTE_IN_SECONDS );
    }
}
