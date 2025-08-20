<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_rainmake_backend_install() {
    global $DB;

    if (!$DB->record_exists('local_rainmake_backend_careerpaths', [])) {
        $defaultdata = [
            (object)[
                'title'       => 'Fundraiser',
                'description' => "Fundraisers are the connectors between good ideas and the people who fund them. In this role, you’ll design campaigns, build donor relationships, and drive fundraising goals to support nonprofits, community groups, and social enterprises. Whether you're organizing events, crafting email appeals, or engaging corporate sponsors, you'll be at the center of financial sustainability for impactful work.",
                'type'        => 'Fundraising',
                'difficulty'  => 'Hard',
                'exp'         => 6500,
            ],
            (object)[
                'title'       => 'Grant Writer',
                'description' => "As a Grant Writer, you'll become the voice of organizations seeking support—turning missions into compelling narratives that resonate with funders. You'll research opportunities, align with grant guidelines, and write powerful proposals that help nonprofits and projects access crucial financial support. This role is ideal for detail-oriented storytellers passionate about making a difference through writing.",
                'type'        => 'Grant-Writing',
                'difficulty'  => 'Moderate',
                'exp'         => 6500,
            ],
            (object)[
                'title'       => 'Grant-Writing for Nonprofit Organizations',
                'description' => "Fundraisers are the connectors between good ideas and the people who fund them. In this role, you’ll design campaigns, build donor relationships, and drive fundraising goals to support nonprofits, community groups, and social enterprises. Whether you're organizing events, crafting email appeals, or engaging corporate sponsors, you'll be at the center of financial sustainability for impactful work.",
                'type'        => 'Fundraising',
                'difficulty'  => 'Hard',
                'exp'         => 6500,
            ],
            (object)[
                'title'       => 'Campaign Manager',
                'description' => "As a Grant Writer, you'll become the voice of organizations seeking support—turning missions into compelling narratives that resonate with funders. You'll research opportunities, align with grant guidelines, and write powerful proposals that help nonprofits and projects access crucial financial support. This role is ideal for detail-oriented storytellers passionate about making a difference through writing.",
                'type'        => 'Grant-Writing',
                'difficulty'  => 'Moderate',
                'exp'         => 6500,
            ],
        ];

        foreach ($defaultdata as $item) {
            $DB->insert_record('local_rainmake_backend_careerpaths', $item);
        }
    }
}
