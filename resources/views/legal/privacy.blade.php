<x-layouts.legal
    :title="__('legal.privacy_title')"
    :updated="__('legal.privacy_updated')"
    :contact="__('legal.privacy_contact')"
    :otherPage="['url' => route('legal.terms'), 'label' => __('Terms of Service')]"
    :sections="[
        [
            'id' => 'intro',
            'chip' => __('legal.privacy_chip_intro'),
            'heading' => __('legal.privacy_intro_heading'),
            'blocks' => [['text' => __('legal.privacy_intro')]],
        ],
        [
            'id' => 'collect',
            'chip' => __('legal.privacy_chip_collect'),
            'heading' => __('legal.privacy_collect_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_collect')],
                ['list' => [
                    __('legal.privacy_collect_name'),
                    __('legal.privacy_collect_push'),
                    __('legal.privacy_collect_timezone'),
                    __('legal.privacy_collect_google'),
                    __('legal.privacy_collect_usage'),
                ]],
            ],
        ],
        [
            'id' => 'use',
            'heading' => __('legal.privacy_use_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_use')],
                ['list' => [
                    __('legal.privacy_use_1'),
                    __('legal.privacy_use_2'),
                    __('legal.privacy_use_3'),
                    __('legal.privacy_use_4'),
                ]],
            ],
        ],
        [
            'id' => 'google',
            'chip' => __('legal.privacy_chip_google'),
            'heading' => __('legal.privacy_google_heading'),
            'highlight' => true,
            'blocks' => [
                ['text' => __('legal.privacy_google_intro')],
                ['subheading' => __('legal.privacy_google_receives_heading')],
                ['list' => [
                    __('legal.privacy_google_receives_id'),
                    __('legal.privacy_google_receives_name'),
                    __('legal.privacy_google_receives_photo'),
                ]],
                ['text' => __('legal.privacy_google_scopes')],
                ['subheading' => __('legal.privacy_google_use_heading')],
                ['text' => __('legal.privacy_google_use')],
                ['subheading' => __('legal.privacy_google_storage_heading')],
                ['text' => __('legal.privacy_google_storage')],
                ['subheading' => __('legal.privacy_google_sharing_heading')],
                ['text' => __('legal.privacy_google_sharing')],
                ['subheading' => __('legal.privacy_google_retention_heading')],
                ['text' => __('legal.privacy_google_retention')],
            ],
        ],
        [
            'id' => 'storage',
            'heading' => __('legal.privacy_storage_heading'),
            'blocks' => [['text' => __('legal.privacy_storage')]],
        ],
        [
            'id' => 'third-party',
            'heading' => __('legal.privacy_third_party_heading'),
            'blocks' => [
                ['text' => __('legal.privacy_third_party')],
                ['list' => [
                    __('legal.privacy_third_party_google'),
                    __('legal.privacy_third_party_push'),
                ]],
            ],
        ],
        [
            'id' => 'rights',
            'chip' => __('legal.privacy_chip_rights'),
            'heading' => __('legal.privacy_rights_heading'),
            'blocks' => [['text' => __('legal.privacy_rights')]],
        ],
    ]"
/>
