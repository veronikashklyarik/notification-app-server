<x-layouts.legal
    :title="__('legal.terms_title')"
    :updated="__('legal.terms_updated')"
    :contact="__('legal.terms_contact')"
    :otherPage="['url' => route('legal.privacy'), 'label' => __('Privacy Policy')]"
    :tabs="[
        [
            'id' => 'acceptance',
            'label' => __('legal.terms_tab_acceptance'),
            'heading' => __('legal.terms_acceptance_heading'),
            'blocks' => [['text' => __('legal.terms_acceptance')]],
        ],
        [
            'id' => 'service',
            'label' => __('legal.terms_tab_service'),
            'heading' => __('legal.terms_service_heading'),
            'blocks' => [
                ['text' => __('legal.terms_service')],
                ['text' => __('legal.terms_service_changes')],
            ],
        ],
        [
            'id' => 'reminders',
            'label' => __('legal.terms_tab_reminders'),
            'heading' => __('legal.terms_reminders_heading'),
            'blocks' => [['text' => __('legal.terms_reminders')]],
        ],
        [
            'id' => 'accounts',
            'label' => __('legal.terms_tab_accounts'),
            'heading' => __('legal.terms_accounts_heading'),
            'blocks' => [
                ['text' => __('legal.terms_accounts')],
                ['text' => __('legal.terms_accounts_google')],
                ['text' => __('legal.terms_accounts_ending')],
            ],
        ],
        [
            'id' => 'prohibited',
            'label' => __('legal.terms_tab_prohibited'),
            'heading' => __('legal.terms_prohibited_heading'),
            'blocks' => [
                ['text' => __('legal.terms_prohibited')],
                ['list' => [
                    __('legal.terms_prohibited_1'),
                    __('legal.terms_prohibited_2'),
                    __('legal.terms_prohibited_3'),
                    __('legal.terms_prohibited_4'),
                ]],
            ],
        ],
        [
            'id' => 'content',
            'label' => __('legal.terms_tab_content'),
            'heading' => __('legal.terms_content_heading'),
            'blocks' => [['text' => __('legal.terms_content')]],
        ],
        [
            'id' => 'ip',
            'label' => __('legal.terms_tab_ip'),
            'heading' => __('legal.terms_ip_heading'),
            'blocks' => [['text' => __('legal.terms_ip')]],
        ],
        [
            'id' => 'liability',
            'label' => __('legal.terms_tab_liability'),
            'heading' => __('legal.terms_liability_heading'),
            'blocks' => [['text' => __('legal.terms_liability')]],
        ],
        [
            'id' => 'changes',
            'label' => __('legal.terms_tab_changes'),
            'heading' => __('legal.terms_changes_heading'),
            'blocks' => [['text' => __('legal.terms_changes')]],
        ],
    ]"
/>
