<?php

return [
    'name' => 'Wpbox',
    'google_maps_api_key'=>env('GOOGLE_MAPS_API_KEY',''),
    'google_maps_enabled'=>env('GOOGLE_MAPS_ENABLED_ON_DETAILS',true),
    'api_docs'=>env('WPBOX_API_DOCS','https://documenter.getpostman.com/view/8538142/2s9Ykn8gvj'),
    'pp_url'=>env('PRIVACY_POLICY_URL', '#'),
    'terms_url'=>env('TERMS_OF_SERVICE_URL', '#'),
    'disclaimer_url'=>env('DISCLAIMER_URL', '#'),
    'tw'=>env('X_URL', '#'),
    'fb'=>env('FACEBOOK_URL', '#'),
    'insta'=>env('INSTAGRAM_URL', '#'),
    "one_signal_app_id"=>env("ONE_SIGNAL_APP_ID",""),
    'openai_api_key'=>env('OPENAI_API_KEY',''),
    'openai_api_key_demo'=>env('OPENAI_API_KEY_DEMO',''),
    'openai_max_tokens'=>env('OPENAI_MAX_TOKENS', 256),
    'openai_enabled'=>env('OPENAI_ENABLED', true),
    'openai_model'=>env('OPENAI_MODEL', 'gpt-3.5-turbo'),
    'available_languages'=>env('AVAILABLE_LANGUAGES', 'English,Spanish,German,Italian,Portuguese,Dutch,French,Japanese,Chinese'),
    'campaign_sending_batch'=>env('CAMPAIGN_SENDING_BATCH', 100),
    'campaign_sending_type'=>env('CAMPAIGN_SENDING_TYPE', 'normal')
];
