<?php

        /**
         * Copy of BP_XProfile_ProfileData::get_all_for_user() from BP version 2.0?
         * Get all of the profile information for a specific user.
         *
         * @param       $user_id        Integer      ID of specific user
         * @since       0.9.6
         * @return      Array           User profile fields
		 * @deprecated	since 1.2.1
         */
        function get_all_for_user( $user_id = null )
		{

            // sanity check ##
            if ( is_null( $user_id ) ) { return false; }

            global $wpdb, $bp;

			$bp = buddypress();

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "
                        SELECT g.id as field_group_id, g.name as field_group_name, f.id as field_id, f.name as field_name, f.type as field_type, d.value as field_data, u.user_login, u.user_nicename, u.user_email
                        FROM {$bp->profile->table_name_groups} g
                            LEFT JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id
                            INNER JOIN {$bp->profile->table_name_data} d ON f.id = d.field_id LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
                        WHERE d.user_id = %d AND d.value != ''
                    "
                    , $user_id
                )
            );

            $profile_data = array();

            if ( ! empty( $results ) ) {

                $profile_data['user_login']    = $results[0]->user_login;
                $profile_data['user_nicename'] = $results[0]->user_nicename;
                $profile_data['user_email']    = $results[0]->user_email;

                foreach( (array) $results as $field ) {

                    $profile_data[$field->field_name] = array(
                        'field_group_id'   => $field->field_group_id,
                        'field_group_name' => $field->field_group_name,
                        'field_id'         => $field->field_id,
                        'field_type'       => $field->field_type,
                        'field_data'       => $field->field_data
                    );

                }

            }

            return $profile_data;

        }



?>