<x-layouts.legal
    :title="__('legal.privacy_title')"
    :updated="__('legal.privacy_updated')"
    :contact="__('legal.privacy_contact')"
    :otherPage="['url' => route('legal.terms'), 'label' => __('Terms of Service')]"
    :tabs="[
        [
            'id' => 'intro',
            'label' => __('legal.privacy_tab_intro'),
            'heading' => __('legal.privacy_intro_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_intro')],
                ['text' => __('legal.privacy_intro_scope')],
                ['text' => __('legal.privacy_intro_short')],
                ['text' => __('legal.privacy_intro_changes')],
            ],
        ],
        [
            'id' => 'collect',
            'label' => __('legal.privacy_tab_collect'),
            'heading' => __('legal.privacy_collect_heading'),
            'blocks' => [
                ['subheading' => __('legal.privacy_collect_account_heading')],
                ['list' => [
                    __('legal.privacy_collect_account_name'),
                    __('legal.privacy_collect_account_password'),
                    __('legal.privacy_collect_account_verified'),
                    __('legal.privacy_collect_account_photo'),
                ]],
                ['subheading' => __('legal.privacy_collect_settings_heading')],
                ['list' => [
                    __('legal.privacy_collect_settings_timezone'),
                    __('legal.privacy_collect_settings_language'),
                ]],
                ['subheading' => __('legal.privacy_collect_reminders_heading')],
                ['list' => [
                    __('legal.privacy_collect_reminders_items'),
                    __('legal.privacy_collect_reminders_marks'),
                ]],
                ['subheading' => __('legal.privacy_collect_push_heading')],
                ['list' => [
                    __('legal.privacy_collect_push_browser'),
                    __('legal.privacy_collect_push_app'),
                ]],
                ['subheading' => __('legal.privacy_collect_technical_heading')],
                ['list' => [
                    __('legal.privacy_collect_technical_logs'),
                    __('legal.privacy_collect_technical_cookies'),
                ]],
                ['text' => __('legal.privacy_collect_not')],
            ],
        ],
        [
            'id' => 'use',
            'label' => __('legal.privacy_tab_use'),
            'heading' => __('legal.privacy_use_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_use')],
                ['list' => [
                    __('legal.privacy_use_reminders'),
                    __('legal.privacy_use_push'),
                    __('legal.privacy_use_email'),
                    __('legal.privacy_use_security'),
                ]],
                ['text' => __('legal.privacy_use_basis')],
                ['text' => __('legal.privacy_use_never')],
            ],
        ],
        [
            'id' => 'google',
            'label' => __('legal.privacy_tab_google'),
            'heading' => __('legal.privacy_google_heading'),
            'highlight' => true,
            'blocks' => [
                ['text' => __('legal.privacy_google_intro')],
                ['subheading' => __('legal.privacy_google_receives_heading')],
                ['text' => __('legal.privacy_google_scopes')],
                ['list' => [
                    __('legal.privacy_google_receives_id'),
                    __('legal.privacy_google_receives_name'),
                    __('legal.privacy_google_receives_photo'),
                ]],
                ['text' => __('legal.privacy_google_saved')],
                ['subheading' => __('legal.privacy_google_use_heading')],
                ['text' => __('legal.privacy_google_use')],
                ['subheading' => __('legal.privacy_google_limited_heading')],
                ['text' => __('legal.privacy_google_limited')],
                ['subheading' => __('legal.privacy_google_storage_heading')],
                ['text' => __('legal.privacy_google_storage')],
                ['subheading' => __('legal.privacy_google_sharing_heading')],
                ['text' => __('legal.privacy_google_sharing')],
                ['subheading' => __('legal.privacy_google_retention_heading')],
                ['text' => __('legal.privacy_google_retention')],
            ],
        ],
        [
            'id' => 'services',
            'label' => __('legal.privacy_tab_services'),
            'heading' => __('legal.privacy_services_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_services')],
                ['list' => [
                    __('legal.privacy_services_hosting'),
                    __('legal.privacy_services_mail'),
                    __('legal.privacy_services_google'),
                    __('legal.privacy_services_push'),
                    __('legal.privacy_services_fonts'),
                ]],
                ['text' => __('legal.privacy_services_none')],
            ],
        ],
        [
            'id' => 'storage',
            'label' => __('legal.privacy_tab_storage'),
            'heading' => __('legal.privacy_storage_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_storage')],
                ['subheading' => __('legal.privacy_retention_heading')],
                ['list' => [
                    __('legal.privacy_retention_account'),
                    __('legal.privacy_retention_history'),
                    __('legal.privacy_retention_reminders'),
                    __('legal.privacy_retention_push'),
                    __('legal.privacy_retention_logs'),
                ]],
                ['subheading' => __('legal.privacy_deletion_heading')],
                ['text' => __('legal.privacy_deletion')],
            ],
        ],
        [
            'id' => 'cookies',
            'label' => __('legal.privacy_tab_cookies'),
            'heading' => __('legal.privacy_cookies_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_cookies')],
                ['list' => [
                    __('legal.privacy_cookies_session'),
                    __('legal.privacy_cookies_xsrf'),
                    __('legal.privacy_cookies_remember'),
                    __('legal.privacy_cookies_locale'),
                ]],
                ['text' => __('legal.privacy_cookies_none')],
            ],
        ],
        [
            'id' => 'rights',
            'label' => __('legal.privacy_tab_rights'),
            'heading' => __('legal.privacy_rights_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_rights_self')],
                ['list' => [
                    __('legal.privacy_rights_edit'),
                    __('legal.privacy_rights_push'),
                    __('legal.privacy_rights_delete'),
                    __('legal.privacy_rights_google'),
                ]],
                ['text' => __('legal.privacy_rights_gdpr')],
                ['text' => __('legal.privacy_rights_children')],
            ],
        ],
    ]"
/>
