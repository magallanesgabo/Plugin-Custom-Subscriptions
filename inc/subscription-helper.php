<?php

final class SubscriptionHelper {

    public static function getSubscriptionById( $subscriptionId ) {
        return get_post( $subscriptionId );
    }

    public static function getPlanById( $planId ) {
       return get_term_by( 'id', $planId, 'subscription-plan' );
    }

    public static function getPlanBySubscriptionId( $subscriptionId ) {
        $planId = get_field( 'subscriptions_sub_plan', $subscriptionId );
        if ( $planId ) {
            $plan = get_term_by( 'id', $planId, 'subscription-plan' );
            return $plan;
        }
        return FALSE;
    }


    public static function getSubscriptionsByUser( $userId, $status = NULL ) {
        $args = array(
            'post_type'      => 'produ-subscription',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'subscriptions_sub_owner',
                    'value'   => $userId,
                    'compare' => '='
                ),
                !is_null( $status ) ? array(
                    'key'     => 'subscriptions_sub_status',
                    'value'   => $status,
                    'compare' => '='
                ) : [],
            )
        );

        $subscriptions = new WP_Query( $args );
        return $subscriptions->posts;
    }

    public static function getSubscriptionsToArchive( $userId, $currentSubscription ) {
        $args = array(
            'post_type'      => 'produ-subscription',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'post__not_in'   => [ $currentSubscription ],
            'meta_query'     => array(
                array(
                    'key'     => 'subscriptions_sub_owner',
                    'value'   => $userId,
                    'compare' => '='
                ),
                array(
                    'key'     => 'subscriptions_sub_status',
                    'value'   => 'archivada',
                    'compare' => '!='
                )
            )
        );

        $subscriptions = new WP_Query( $args );
        return $subscriptions->posts;
    }

    public static function getSubscriptionStatus( $subscriptionId ) {
        return get_field( 'subscriptions_sub_status', $subscriptionId );
    }

    public static function setSubscriptionStatus( $subscriptionId, $status ) {
        return update_field( 'subscriptions_sub_status', $status, $subscriptionId );
    }

    public static function getSubscriptionByUser( $userId ) {
        $args = array(
            'post_type'      => 'produ-subscription',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'subscriptions_sub_owner',
                    'value'   => $userId,
                    'compare' => '='
                )
            )
        );

        $latestSubscriptionQuery = new WP_Query( $args );
        if ( $latestSubscriptionQuery->have_posts() ) {
            $latestSubscriptionQuery->the_post();
            $latestSubscription = get_post();
            wp_reset_postdata();
            return $latestSubscription->ID;
        } else {
            return NULL;
        }
    }

    public static function getLastPlan( $userId ) {
        $latestSubscription = self::getSubscriptionByUser( $userId );
        if  ( $latestSubscription !== NULL ) {
            $planId = get_field( 'subscriptions_sub_plan', $latestSubscription );
            $plan = get_term_by( 'id', $planId, 'subscription-plan' );
            return $plan;
        } else {
            return NULL;
        }
    }

    public static function getSubscriptionByUserMeta( $userId ) {
        $subscriptionId = get_user_meta( $userId, '_wp_user_subscription_subscription_id', TRUE );
        if ( !empty($subscriptionId) ) {
            $subscription = get_post( $subscriptionId );
            return $subscription;
        } else {
            return NULL;
        }
    }

    public static function getPlanByUserMeta( $userId ) {
        $planId = get_user_meta( $userId, '_wp_user_subscription_plan_id', TRUE );
        if ( !empty( $planId ) ) {
            $plan = get_term_by( 'id', $planId, 'subscription-plan' );
            return $plan;
        } else {
            return NULL;
        }
    }

    public static function getRelatedOrOwnSubscription( $userId ) {
        $related = get_user_meta( $userId, '_wp_user_subscription_related_subscription_id', TRUE );
        if ( $related ) {
            $status = self::getSubscriptionStatus( $related );
            if ( $status === 'activa' ) {
                return self::getSubscriptionById( $related );
            } else {
                return self::getSubscriptionByUserMeta( $userId );
            }
        } else {
            return self::getSubscriptionByUserMeta( $userId );
        }
    }

    public static function generateUniqueUsername( $username ) {
        $original_username = $username;
        $counter = 1;

        while ( username_exists( $username ) ) {
            $username = $original_username.$counter;
            $counter++;
        }
        return $username;
    }

    public static function isCorporate( $subscriptionId ) : int {
        $typeId = get_field( 'subscriptions_sub_type', $subscriptionId );
        $typeTerm = get_term_by( 'id', $typeId, 'subscription-type' );

        if ( $typeTerm && !is_wp_error( $typeTerm )) {
            if ( $typeTerm->slug === 'corporativa' ) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    public static function corporateBeneficiary( $userId ) : int {
        $userTerm = get_user_meta( $userId, '_wp_user_subscription_related_subscription_id', TRUE );
        if ( $userTerm && !is_wp_error( $userTerm ) ) {
            $subscription = self::getSubscriptionById( $userTerm );
            if ( $subscription && isset( $subscription->ID ) ) {
                $beneficiaries = self::getBeneficiariesInSubscription(  $subscription->ID  );
                if ( in_array( $userId, $beneficiaries ) ) {
                    return (int) $subscription->ID;
                }
                return 0;
            }
            return -1;
        }
        return -2;
    }

    /**
     * Obtiene los newsletters y listas asociadas a un plan específico.
     *
     * Esta función devuelve un array con dos claves: newsletter, con el nombre del newsletter y mailchimp, con un arreglo de las listas asociadas de mailchimp a
     * ese newsletter, incluyendo id de lista, id de categoría, y arreglo con ids de grupos
     *
     * @param int $planWpid El ID de término de taxonomía del plan.
     * @return array El array de listas mailchimp asociadas al newsletter.
     */
    public static function getMailchimpListIdByPlan( $planWpid ) : array {
        $mailchimpLists = [];
        $newsletters = self::getNewslettersFromPlan( $planWpid );

        if ( is_array( $newsletters ) && count( $newsletters ) > 0 ) {
            foreach ( $newsletters as $newsletter ) {
                $rawMailchimpList = self::getMailchimpListId( $newsletter );
                if ( count( $rawMailchimpList ) > 0 ) {
                    $newsletterTerm = get_term_by( 'id', $newsletter, 'newsletter_type' );

                    $mailchimpLists[] = [
                        'newsletter'    => $newsletterTerm->name,
                        'mailchimp'     => $rawMailchimpList,
                    ];
                }
            }
        }
        return $mailchimpLists;
    }

    public static function getUniqueMailchimpListIdsByPlan( $planWpid ) : array {
        $mailchimpLists = [];
        $newsletters = self::getNewslettersFromPlan( $planWpid );

        if ( is_array( $newsletters ) && count( $newsletters ) > 0 ) {
            foreach ( $newsletters as $newsletter ) {
                $wpLists = get_field( 'meta_newsletter_mailchimp_lists', "term_$newsletter" );
                if ( is_array( $wpLists ) ) {
                    foreach($wpLists as $wpList) {
                        $rawList = get_field( 'meta_mailchimplist_id',  $wpList['list'] );
                        if ( $rawList ) {
                            $mailchimpLists[] = $rawList;
                        }
                    }
                }
            }
        }
        return array_unique( $mailchimpLists );
    }

    /**
     * Obtiene los ID de newsletters asociadas a un plan específico.
     *
     * Esta función devuelve un array con los id WP asociadas al plan identificado por su ID de término de taxonomía.
     *
     * @param int $planWpid El ID de término de taxonomía del plan.
     * @return array El array de newsletters asociadas al plan.
     */
    public static function getNewslettersFromPlan( $planWpid ) : array {
        $fields = get_field( 'plans_plan_benefits', "term_$planWpid" );

        if ( is_array( $fields ) && isset( $fields['plans_plan_newsletter'] ) ) {
            return $fields['plans_plan_newsletter'];
        }
        return [];
    }

    /**
     * Obtiene los ID de newsletters asociadas al plan por defecto.
     *
     * Esta función devuelve un array con los id WP asociadas al plan default
     *
     * @return array El array de newsletters asociadas al plan.
     */
    public static function getNewslettersDefault( ) : array {
        $default = get_term_by('slug', 'default', 'subscription-plan');
        if ( $default ) {
            $fields = get_field( 'plans_plan_benefits', "term_$default->term_id" );

            if ( is_array( $fields ) && isset( $fields['plans_plan_newsletter'] ) ) {
                return $fields['plans_plan_newsletter'];
            }
        }
        return [];
    }

    /**
     * Obtiene los ID de las listas, categorías y grupos mailchimp asociadas a un newsletter específico.
     *
     * Esta función devuelve un array con los id de lista|categoría|grupos mailchimp asociadas al newsletter identificado por su ID de término de taxonomía.
     *
     * @param int $newsletterWpid El ID de término de taxonomía del newsletter.
     * @return array El array de ids de mailchimp asociadas al newsletter.
     */
    public static function getMailchimpListId( $newsletterWpid ) : array {
        $wpLists = get_field( 'meta_newsletter_mailchimp_lists', "term_$newsletterWpid" );
        $mailchimpLists = [];

        if ( is_array( $wpLists ) ) {
            foreach($wpLists as $wpList) {
                $rawList = get_field( 'meta_mailchimplist_id',  $wpList['list'] );
                $rawCategory = '';
                $rawGroups = [];

                if ( $rawList ) {
                    if ( isset( $wpList['category'] ) && $wpList['category'] ) {
                        $rawCategory = get_field( 'meta_mailchimp_category_id', 'term_'.$wpList['category'] );

                        if ($rawCategory) {
                            if ( isset( $wpList['groups'] ) && is_array( $wpList['groups'] ) ) {
                                foreach ( $wpList['groups'] as $group ) {
                                    $rawItemGroup = get_field( 'meta_mailchimp_group_id', "term_$group" );
                                    if ( $rawItemGroup ) $rawGroups[] = $rawItemGroup;
                                }
                            }
                        }
                    }

                    $mailchimpLists[] = [
                        'list_id'       => $rawList,
                        'category_id'   => $rawCategory,
                        'groups_id'     => $rawGroups,
                    ];
                }
            }
        }
        return $mailchimpLists;
    }


    /**
     * Obtiene los ids de intereses de mailchimp asociados a un lista mailchimp específica.
     *
     * @param string $mailchimpListId El ID alfanumérico de la lista mailchimp.
     * @return array El array de ids de interests (groups) asociadas la lista mailchimp.
     */
    public static function getInterestsFromMailchimpListId( $mailchimpListId ) : array {
        global $wpdb;
        $mailchimpGroups = [];
        $query = $wpdb->prepare("SELECT p.*
                                FROM {$wpdb->posts} p
                                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                                WHERE p.post_type = %s
                                AND pm.meta_key = %s
                                AND pm.meta_value = %s
                                LIMIT 1;
                                ",
                                'produ_listamailchimp',
                                'meta_mailchimplist_id',
                                $mailchimpListId
                            );
        $list = $wpdb->get_row($query);

        if ($list) {
            $mailchimpGroups = self::getInterestsFromListId($list->ID);
        }

        return $mailchimpGroups;
    }

    /**
     * Obtiene los ids de intereses de mailchimp asociados a un lista wp específica.
     *
     * @param int $wpListId El ID de término de taxonomía de la lista wp.
     * @return array El array de ids de interests (groups) asociadas la lista wp.
     */
    public static function getInterestsFromListId( $wpListId ) : array {
        global $wpdb;
        $mailchimpGroups = [];
        $query = $wpdb->prepare( "SELECT t.*
                                FROM {$wpdb->terms} t
                                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                                INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
                                WHERE tt.taxonomy = %s
                                AND tm.meta_key = %s
                                AND tm.meta_value = %s
                                ",
                                'mailchimp_group',
                                'meta_mailchimp_list',
                                $wpListId );

        $groups = $wpdb->get_results( $query );
        if ( $groups ) {
            foreach ( $groups as $group ) {
                $term = get_field( 'meta_mailchimp_group_id', 'term_'.$group->term_id );
                if ( $term ) {
                    $mailchimpGroups[] = $term;
                }
            }
        }
        return array_values( array_unique( $mailchimpGroups ) );
    }

    public static function getDefaultPlan( ) : array {
        $defaultPlan = [];
        $default = get_term_by( 'slug', 'default', 'subscription-plan' );
        if ( $default ) {
            $fields = get_fields( "term_$default->term_id" );

            $defaultPlan = [
                'plan'      => $default,
                'fields'    => $fields,
            ];
        }
        return $defaultPlan;
    }

    public static function getSubscriptionPreferences( $subscriptionId, $ownerId ) : array {
        global $wpdb;

        $preferences = [];
        $tableName = $wpdb->prefix.'subscription_preferences';
        $result = $wpdb->prepare( "SELECT * FROM $tableName WHERE subscription_id = %d AND user_id = %d LIMIT 1;", array( $subscriptionId, $ownerId ) );
        $row = $wpdb->get_row( $result );
        if ( isset( $row->preferences ) ) {
            $preferences = json_decode( $row->preferences, TRUE );
        }

        return $preferences;
    }

    public static function checkPreferences( $newsletters, $preferences ) {
        $newPreferences = [];
        $currentPreferences = [];
        $deletePreferences = [];

        $preferencesMap = [];
        foreach ( $preferences as $preference ) {
            $preferencesMap[ $preference['newsletter_wpid'] ] = $preference;
        }

        #Vigentes|Nuevos
        foreach ( $newsletters as $newsletter ) {
            if ( isset( $preferencesMap[ $newsletter ] ) ) {
                $currentPreferences[] = $preferencesMap[$newsletter];
            } else {
                $newPreferences[] = [ 'newsletter_wpid' => $newsletter, 'status' => 'suscribed' ];
            }
        }

        #Sobrantes, por eliminar
        foreach ( $preferences as $preference ) {
            if ( !in_array( $preference['newsletter_wpid'], $newsletters ) ) {
                $deletePreferences[] = $preference;
            }
        }

        return [
            'new'       => $newPreferences,
            'current'   => $currentPreferences,
            'delete'    => $deletePreferences,
        ];
    }

    public static function validatePreferences( $prevPreferences, $postPreferences ) {
        $output = [];

        $prevPreferencesAssoc = [];
        foreach ( $prevPreferences as $prevPreference ) {
            $prevPreferencesAssoc[ $prevPreference['newsletter_wpid'] ] = $prevPreference['local_status'];
        }

        $postPreferencesAssoc = [];
        foreach ( $postPreferences as $postPreference ) {
            $postPreferencesAssoc[ $postPreference['newsletter_wpid'] ] = $postPreference['local_status'];
        }

        foreach ( $postPreferences as $postPreference ) {
            if ( !array_key_exists( $postPreference['newsletter_wpid'], $prevPreferencesAssoc ) ) {
                $output[] = $postPreference;
            }
        }

        foreach ( $prevPreferences as $prevPreference ) {
            if ( !array_key_exists( $prevPreference['newsletter_wpid'], $postPreferencesAssoc ) && $prevPreference['local_status'] === 'suscribed' ) {
                $output[] = $prevPreference;
            }
        }

        foreach ( $postPreferences as $postPreference ) {
            if ( array_key_exists( $postPreference['newsletter_wpid'], $prevPreferencesAssoc ) && $postPreference['local_status'] !== $prevPreferencesAssoc[ $postPreference['newsletter_wpid'] ] ) {
                $output[] = $postPreference;
            }
        }

        return $output;
    }

    public static function preferencesExists( $subscriptionId, $ownerId ) {
        global $wpdb;

        $tableName = esc_sql( $wpdb->prefix.'subscription_preferences' );

        $querySub = $wpdb->prepare( "SELECT id FROM $tableName WHERE subscription_id = %d AND user_id = %d LIMIT 1;", array( $subscriptionId, $ownerId ) );
        $exist = $wpdb->get_var($querySub);
        return $exist;
    }

    public static function setPreferences( $subscriptionId, $postPreferences, $userId = NULL ) {
        global $wpdb;

        if ( is_countable( $postPreferences ) && count( $postPreferences ) > 0 ) {
            $tableName = esc_sql( $wpdb->prefix.'subscription_preferences' );

            $today = date('Y-m-d H:i:s');
            $plan = get_field( 'subscriptions_sub_plan', $subscriptionId );

            if ( $userId === NULL ) {
                $userId = get_field( 'subscriptions_sub_owner', $subscriptionId );
            }
            $user = get_user_by( 'id', $userId );

            $preferences = [
                'subscription_id'   => $subscriptionId,
                'plan_id'           => $plan,
                'user_id'           => $userId,
                'email'             => $user->user_email,
                'preferences'       => json_encode( $postPreferences ),
            ];

            $exist = self::preferencesExists( $subscriptionId, $userId );

            if ( $exist ) {
                $updated = $wpdb->update( $tableName, $preferences, ['id' => $exist] );
            } else {
                $preferences['created_at'] = $today;
                $preferences['updated_at'] = $today;
                $updated = $wpdb->insert( $tableName, $preferences );
            }

            return $updated;
        } else {
            return FALSE;
        }
    }

    public static function saveMailchimpPreferences ( $processed, $userId ) {
        $MailChimp = new \DrewM\MailChimp\MailChimp( MAILCHIMP_API_KEY );

        $user = get_user_by('id', $userId);
        $email = $user->user_email;
        $subscriberHash = $MailChimp->subscriberHash( $email );

        $processedLists = [];
        $messages = [];

        foreach ( $processed as $lists ) {
            foreach ( $lists['lists'] as $list ) {
                if ( is_countable( $list['groups_id'] ) && count( $list['groups_id'] ) > 0 ) {
                    foreach ( $list['groups_id'] as $group ) {
                        $processedLists[ $list['list_id'] ]['interests']["$group"] = $lists['local_status'] === 'subscribed' ? TRUE : FALSE;
                        $processedLists[ $list['list_id'] ]['status'] = 'subscribed';
                    }
                } else {
                    if ( isset( $processedList[ $list['list_id'] ]['interests'] ) && count( $processedLists[ $list['list_id'] ]['interests'] ) > 0 ) {
                        #Si hay al menos un interest, mantengo el status de suscrito a la lista
                        $processedLists[ $list['list_id'] ]['status'] = 'subscribed';
                    } else {
                        $processedLists[ $list['list_id'] ]['status'] = $lists['local_status'];
                    }
                }
            }
        }

        if ( count( $processedLists ) > 0 ) {
            foreach ($processedLists as $listId => $processedList) {
                $mailchimpExists = $MailChimp->get("lists/$listId/members/$subscriberHash");

                #Si la lista tiene otros interests, esto ayuda a limpiar suscripciones a esos interests
                $otherInterests = self::getInterestsFromMailchimpListId( $listId );
                if ( is_countable( $otherInterests ) && count( $otherInterests ) > 0 ) {
                    foreach ( $otherInterests as $otherInterest ) {
                        if ( !array_key_exists( $otherInterest,  $processedList['interests'] ) ) {
                            $processedList['interests'][$otherInterest] = FALSE;
                        }
                    }
                }

                if ( $MailChimp->success() && isset( $mailchimpExists['id'] ) && $mailchimpExists['id'] ) {
                    #patch
                    $memberData = [
                        'status'    => $processedList['status'],
                        'interests' => $processedList['interests'],
                    ];
                    $result = $MailChimp->patch("lists/$listId/members/$subscriberHash", $memberData);
                    $messages[$listId] = $result;
                } else {
                    #post
                    $memberData = [
                        'email_address' => $email,
                        'status'        => $processedList['status'],
                        'merge_fields'  => [
                            'FNAME' => $user->first_name,
                            'LNAME' => $user->last_name
                        ],
                        'interests' => $processedList['interests'],
                    ];
                    $result = $MailChimp->post("lists/$listId/members", $memberData);
                    $messages[$listId] = $result;
                }
            }
        }

        return $messages;
    }

    public static function superSetPreferences( $newSubscriptionId, $currentSubcriptionId, $userId, $default = NULL ) {
        $response = [];
        $plan = get_field( 'subscriptions_sub_plan', $newSubscriptionId );

        if ( $default === NULL ) {
            $newsletters = self::getNewslettersFromPlan( $plan );
        } else {
            $newsletters = self::getNewslettersDefault( );
        }

        $preferences = self::getSubscriptionPreferences( $currentSubcriptionId, $userId );

        #Completo la info de los nuevos newsletters
        $checkedPreferences = self::checkPreferences( $newsletters, $preferences );
        if ( isset( $checkedPreferences['new'] ) && count( $checkedPreferences['new'] ) > 0 ) {
            foreach ( $checkedPreferences['new'] as &$new ) {
                $new['newsletter_wpid'] = (int) $new['newsletter_wpid'];
                $new['local_status'] = 'subscribed';
                $new['lists'] = self::getMailchimpListId( $new['newsletter_wpid'] );
                $new['status'] = '';
            }
        }

        #Merge es lo que debo registrar en las preferencias db
        $merge = array_merge( $checkedPreferences['new'], $checkedPreferences['current'] );
        $response['setDB'] = self::setPreferences( $newSubscriptionId, $merge, $userId );

        if ( isset( $checkedPreferences['delete'] ) && count( $checkedPreferences['delete'] ) > 0 ) {
            foreach ( $checkedPreferences['delete'] as &$delete ) {
                $delete['local_status'] = 'unsubscribed';
            }
        }

        $againstMailchimp = array_merge( $merge, $checkedPreferences['delete'] );
        $response['setMC'] = self::saveMailchimpPreferences( $againstMailchimp, $userId );

        return $response;
    }

    public static function superSetPreferencesInactive( $currentSubcriptionId, $userId ) {
        $response = [];
        $plan = get_field( 'subscriptions_sub_plan', $currentSubcriptionId );

        $newsletters = self::getNewslettersFromPlan( $plan );

        #Completo la info de los newsletters
        if ( is_countable( $newsletters ) && count( $newsletters ) > 0 ) {
            foreach ( $newsletters as $newsletter ) {
                $againstMailchimp[] = [
                    'newsletter_wpid'   => (int) $newsletter,
                    'local_status'      => 'unsubscribed',
                    'status'            => 'unsubscribed',
                    'lists'             => self::getMailchimpListId( $newsletter )
                ];
            }
        }
        $response['setDB'] = self::setPreferences( $currentSubcriptionId, $againstMailchimp );
        $response['setMC'] = self::saveMailchimpPreferences( $againstMailchimp, $userId );
        return $response;
    }

    public static function getFormatedBeneficiariesInSubscription( $subscriptionId ) : array {
        $beneficiaries = [];

        if ( have_rows( 'subscriptions_sub_beneficiaries', $subscriptionId ) ) {
            while ( have_rows( 'subscriptions_sub_beneficiaries', $subscriptionId ) ) {
                the_row();
                $beneficiary = get_sub_field( 'subscriptions_sub_user' );
                $login = -1;
                if ( metadata_exists('user', $beneficiary, '_wp_user_subscription_login_enabled' ) ) {
                    $login = get_user_meta( $beneficiary, '_wp_user_subscription_login_enabled', TRUE );
                }
                $userInfo = get_userdata( $beneficiary );
                $beneficiaries[] = [
                    'id'    => (int) $beneficiary,
                    'name'  => $userInfo->first_name.' '.$userInfo->last_name,
                    'login' => (int) $login,
                ];
            }
        }

        return $beneficiaries;
    }

    public static function getBeneficiariesInSubscription( $subscriptionId ) : array {
        $beneficiaries = [];

        if ( have_rows( 'subscriptions_sub_beneficiaries', $subscriptionId ) ) {
            while ( have_rows( 'subscriptions_sub_beneficiaries', $subscriptionId ) ) {
                the_row();
                $beneficiary = get_sub_field( 'subscriptions_sub_user' );
                if ( $beneficiary ) $beneficiaries[] = (int) $beneficiary;
            }
        }

        return $beneficiaries;
    }

    public static function transformPostBeneficiaries( $beneficiaries ) {
        $transformedArray = [];
        foreach ( $beneficiaries as $beneficiary ) {
            if ( isset( $beneficiary['field_66219f37bce1d'] ) && $beneficiary['field_66219f37bce1d'] ) {
                $login = -1;
                if ( metadata_exists('user', $beneficiary['field_66219f37bce1d'], '_wp_user_subscription_login_enabled' ) ) {
                    $login = get_user_meta( $beneficiary['field_66219f37bce1d'], '_wp_user_subscription_login_enabled', TRUE );
                }
                $userInfo = get_userdata( $beneficiary['field_66219f37bce1d'] );
                $transformedArray[] = [
                    'id'    => (int) $beneficiary['field_66219f37bce1d'],
                    'name'  => $userInfo->first_name.' '.$userInfo->last_name,
                    'login' => (int) $login,
                ];
            }
        }
        return $transformedArray;
    }

    public static function compareBeneficiaries( $subscriptionId, $postData ) {
        $deleteBeneficiaries = [];
        $newBeneficiaries = [];
        $currentBeneficiaries = [];

        #Obtenemos beneficiarios formateados desde la suscripción
        $current = self::getFormatedBeneficiariesInSubscription( $subscriptionId );

        #Convertimos ACF a formato beneficiario
        $transformedPostDataArray = self::transformPostBeneficiaries( $postData );

        $arrayCurrentById = [];
        foreach ( $current as $item ) {
            $arrayCurrentById[$item['id']] = $item;
        }

        $transformedArrayPostById = [];
        foreach ( $transformedPostDataArray as $item ) {
            $transformedArrayPostById[$item['id']] = $item;
        }

        #Beneficiarios vigentes | sobrantes (ya no serán beneficiarios)
        foreach ( $arrayCurrentById as $id => $item ) {
            if ( isset( $transformedArrayPostById[$id] ) ) {
                $currentBeneficiaries[] = $item;
            } else {
                $deleteBeneficiaries[] = $item;
            }
        }

        #Nuevos beneficiarios
        foreach ( $transformedArrayPostById as $id => $item ) {
            if (! isset( $arrayCurrentById[$id] ) ) {
                $newBeneficiaries[] = $item;
            }
        }

        return [
            'new'       => $newBeneficiaries,
            'current'   => $currentBeneficiaries,
            'delete'    => $deleteBeneficiaries,
            'postdata'  => $transformedPostDataArray,
        ];
    }

    public static function createDefaultSubscription( $userId ) : int  {
        $userInfo = get_userdata( $userId );
        $userFields = get_fields( "user_$userId" );

        $today = new DateTime( current_time( 'mysql' ) );

        $defaultPlan = self::getDefaultPlan();

        if ( isset( $defaultPlan['plan'] ) ) {
            $plan = $defaultPlan['plan'];
            $planFields = $defaultPlan['fields'];

            #Crea la suscripción en WP
            $title = "$userInfo->first_name $userInfo->last_name";
            $newPost = array(
                'post_title'    => $title,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'produ-subscription',
                'post_date'     => $today->format( 'Y-m-d H:i:s' ),
            );

            $newSubscriptionId = wp_insert_post( $newPost );

            if ( $newSubscriptionId ) {
                #Suscripción
                update_field( 'subscriptions_sub_type', $planFields['plans_plan_type'], $newSubscriptionId );
                update_field( 'subscriptions_sub_plan',  $plan->term_id, $newSubscriptionId );
                update_field( 'subscriptions_sub_owner', $userId, $newSubscriptionId );
                update_field( 'subscriptions_sub_begin_date', $today->format( 'Ymd' ), $newSubscriptionId );
                update_field( 'subscriptions_sub_end_date', '', $newSubscriptionId );
                update_field( 'subscriptions_sub_status', 'activa', $newSubscriptionId );
                update_post_meta( $newSubscriptionId, 'subscriptions_sub_grace_period', 0 );

                #Facturación
                update_field( 'billing_name', $title, $newSubscriptionId );
                update_field( 'billing_email', $userInfo->data->user_email, $newSubscriptionId );
                update_field( 'billing_phone', $userFields['phone'], $newSubscriptionId );
                update_field( 'billing_company', $userFields['subscriber_company'], $newSubscriptionId );
                update_field( 'billing_address', $userFields['address'], $newSubscriptionId );

                #Pago
                $method = FALSE;
                $methodTerm = get_term_by( 'slug', 'gratis', 'subscription-payment-method' );
                if ( $methodTerm && ! is_wp_error( $methodTerm ) ) {
                    $method = $methodTerm->term_id;
                }
                update_field( 'payments_method', $method, $newSubscriptionId );
                update_field( 'payments_plan_amount', '0', $newSubscriptionId );
                update_field( 'payments_discount', '0', $newSubscriptionId );
                update_field( 'payments_surcharge', '0', $newSubscriptionId );
                update_field( 'payments_manual_amount', '0', $newSubscriptionId );
                update_field( 'payments_amount', '0', $newSubscriptionId );
                update_field( 'payments_date', $today->format( 'Y-m-d' ), $newSubscriptionId );
                update_field( 'payments_status', 'aprobado', $newSubscriptionId );
                update_field( 'payments_description', '', $newSubscriptionId );
                update_field( 'payments_bank', FALSE, $newSubscriptionId );
                update_field( 'payments_card', FALSE, $newSubscriptionId );

                #Metas
                update_user_meta( $userId, '_wp_user_subscription_plan_id', $plan->term_id );
                update_user_meta( $userId, '_wp_user_subscription_subscription_id', $newSubscriptionId );
                update_user_meta( $userId, '_wp_user_subscription_login_enabled', '1' );
                update_user_meta( $userId, '_wp_user_subscription_enabled', '1' );

                $iniatialPlan = get_user_meta( $userId, '_wp_user_subscription_initial_plan_id', TRUE );
                if ( !$iniatialPlan ) {
                    update_user_meta( $userId, '_wp_user_subscription_initial_plan_id', $plan->term_id );
                }

                $initialSubscription = get_user_meta( $userId, '_wp_user_subscription_initial_subscription_id', TRUE );
                if ( !$initialSubscription ) {
                    update_user_meta( $userId, '_wp_user_subscription_initial_subscription_id', $newSubscriptionId );
                }

                $memberSince = get_user_meta( $userId, '_wp_user_subscription_initial_subscription_id', TRUE );
                if ( !$memberSince || $memberSince === '0000-00-00' ) {
                    update_user_meta( $userId, '_wp_user_subscription_member_since', $today->format( 'Y-m-d' ) );
                }

                return (int) $newSubscriptionId;
            }  else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    public static function deletePreferencesFromDb( $subscriptionId, $userId ) {
        global $wpdb;

        $tableName = $wpdb->prefix.'subscription_preferences';
        $deleted = $wpdb->delete( $tableName, [ 'subscription_id' => $subscriptionId, 'user_id' => $userId ], [ '%d', '%d' ] );
        if ( $deleted ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public static function pageExists( $title ) {
        $page = get_page_by_title( $title, OBJECT, 'page' );
        return $page ? $page->ID : FALSE;
    }

    public static function subscriptionGetHeader( $name = NULL ) {
        $templatePath = PRODUSUBSCRIPTION__PLUGIN_DIR.'templates/frontend/';

        if ( $name ) {
            $file = $templatePath."{$name}.php";
        } else {
            $file = $templatePath.'custom-header.php';
        }

        if ( file_exists( $file ) ) {
            include $file;
        } else {
            get_header( $name );
        }
    }

    public static function subscriptionGetFooter( $name = NULL ) {
        $templatePath = PRODUSUBSCRIPTION__PLUGIN_DIR.'templates/frontend/';

        if ( $name ) {
            $file = $templatePath."{$name}.php";
        } else {
            $file = $templatePath.'custom-footer.php';
        }

        if ( file_exists( $file ) ) {
            include $file;
        } else {
            get_footer( $name );
        }
    }
}

