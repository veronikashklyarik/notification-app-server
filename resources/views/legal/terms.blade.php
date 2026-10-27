<x-layouts.legal
    :title="__('legal.terms_title')"
    :updated="__('legal.terms_updated')"
    :contact="__('legal.terms_contact')"
    :otherPage="['url' => route('legal.privacy'), 'label' => __('Privacy Policy')]"
    :sections="[
        [
            'id' => 'acceptance',
            'chip' => __('legal.terms_chip_acceptance'),
            'heading' => __('legal.terms_acceptance_heading'),
            'blocks' => [['text' => __('legal.terms_acceptance')]],
        ],
        [
            'id' => 'service',
            'chip' => __('legal.terms_chip_service'),
            'heading' => __('legal.terms_service_heading'),
            'blocks' => [['text' => __('legal.terms_service')]],
        ],
        [
            'id' => 'reminders',
            'chip' => __('legal.terms_chip_reminders'),
            'heading' => __('legal.terms_reminders_heading'),
            'blocks' => [['text' => __('legal.terms_reminders')]],
        ],
        [
            'id' => 'accounts',
            'chip' => __('legal.terms_chip_accounts'),
            'heading' => __('legal.terms_accounts_heading'),
            'blocks' => [['text' => __('legal.terms_accounts')]],
        ],
        [
            'id' => 'prohibited',
            'chip' => __('legal.terms_chip_prohibited'),
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
            'id' => 'ip',
            'heading' => __('legal.terms_ip_heading'),
            'blocks' => [['text' => __('legal.terms_ip')]],
        ],
        [
            'id' => 'liability',
            'chip' => __('legal.terms_chip_liability'),
            'heading' => __('legal.terms_liability_heading'),
            'blocks' => [['text' => __('legal.terms_liability')]],
        ],
        [
            'id' => 'changes',
            'heading' => __('legal.terms_changes_heading'),
            'blocks' => [['text' => __('legal.terms_changes')]],
        ],
    ]"
/>
