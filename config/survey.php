<?php

/*
|--------------------------------------------------------------------------
| Survey questions
|--------------------------------------------------------------------------
|
| Questions live here, not in the database — changing a question is a git
| commit. Each question is keyed by a stable identifier used in URLs and
| as the key inside survey_responses.answers. Questions with a `column`
| write to that promoted column on survey_responses instead.
|
| `label` is either a string (identical on both channels) or a per-channel
| array. Items pooled across channels MUST stay identically worded.
|
| `condition` maps a previously answered question key to the set of answer
| values for which this question is shown.
|
| `sequences` defines the question order per channel; a question appears
| on a channel if and only if its key is listed in that channel's sequence.
|
*/

$barrierOptions = [
    'no_size' => "Didn't have my size",
    'no_style' => "Didn't have the style I wanted",
    'price' => 'Price',
    'no_help' => "Couldn't find anyone to help",
    'checkout_line' => 'Checkout line too long',
    'just_browsing' => 'Just browsing',
];

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Address that hears about every respondent who checks the "follow up
    | with me" box.
    |
    */

    'follow_up_notification_email' => env('SURVEY_FOLLOW_UP_EMAIL', 'marketing@sportscenter242.com'),

    'questions' => [

        'primary_location' => [
            'type' => 'single_choice',
            'label' => 'Which location do you shop at most often?',
            'options' => [
                'harbour-bay-sc' => 'The Sports Center — Harbour Bay',
                'marathon-mall-sc' => 'The Sports Center — Marathon Mall',
                'sandyport-sc' => 'The Sports Center — Sandyport',
                'harbour-bay-hw' => 'HeatWave — Harbour Bay',
                'sandyport-hw' => 'HeatWave — Sandyport',
                'harbour-bay-outlet' => 'The Outlet — Harbour Bay',
                'multiple' => 'I shop at more than one',
            ],
        ],

        'departments' => [
            'type' => 'multi_select',
            'label' => [
                'qr' => 'Which department(s) did you shop in today?',
                'email' => 'Which department(s) do you usually shop in?',
            ],
            'options' => [
                'mens_apparel' => "Men's Apparel",
                'womens_apparel' => "Women's Apparel",
                'kids_apparel' => 'Kids Apparel',
                'footwear' => 'Footwear',
                'fitness_equip' => 'Fitness & Sporting Equipment',
            ],
        ],

        'last_shopped' => [
            'type' => 'single_choice',
            'label' => 'When did you last shop with us?',
            'options' => [
                'past_month' => 'Within the past month',
                'one_to_three_months' => '1–3 months',
                'three_to_six_months' => '3–6 months',
                'six_to_twelve_months' => '6–12 months',
                'over_a_year' => 'Over a year ago',
            ],
        ],

        'shop_frequency' => [
            'type' => 'single_choice',
            'label' => 'How often do you shop with us?',
            'options' => [
                'weekly' => 'Weekly',
                'few_times_month' => 'A few times a month',
                'every_few_months' => 'Every few months',
                'couple_times_year' => 'A couple times a year',
                'first_visit' => 'First visit',
            ],
        ],

        'overall_score' => [
            'type' => 'scale_1_10',
            'column' => 'overall_score',
            'label' => 'On a scale from 1 to 10, how would you rate your overall shopping experience with us?',
            'hint' => '1 = Poor · 10 = Excellent',
        ],

        'found_what_looking_for' => [
            'type' => 'likert',
            'column' => 'found_what_looking_for',
            'preamble' => [
                'qr' => 'During my visit today…',
                'email' => 'During my last visit…',
            ],
            'label' => 'I was able to find what I was looking for.',
        ],

        'staff_knowledgeable' => [
            'type' => 'likert',
            'column' => 'staff_knowledgeable',
            'preamble' => [
                'qr' => 'During my visit today…',
                'email' => 'During my last visit…',
            ],
            'label' => 'The staff were knowledgeable about the products I was interested in.',
        ],

        'staff_helpful' => [
            'type' => 'likert',
            'column' => 'staff_helpful',
            'preamble' => [
                'qr' => 'During my visit today…',
                'email' => 'During my last visit…',
            ],
            'label' => 'The staff were helpful.',
        ],

        'store_clean' => [
            'type' => 'likert',
            'column' => 'store_clean',
            'preamble' => [
                'qr' => 'During my visit today…',
                'email' => 'During my last visit…',
            ],
            'label' => 'I found the store clean and easy to navigate.',
        ],

        'prices_fair' => [
            'type' => 'likert',
            'column' => 'prices_fair',
            'preamble' => [
                'qr' => 'During my visit today…',
                'email' => 'During my last visit…',
            ],
            'label' => 'I feel the prices are fair for the quality of the products.',
        ],

        'greeted' => [
            'type' => 'single_choice',
            'label' => 'Were you greeted by a staff member?',
            'options' => [
                'yes' => 'Yes',
                'no' => 'No',
                'dont_remember' => "Don't remember",
            ],
        ],

        'thanked_at_checkout' => [
            'type' => 'single_choice',
            'label' => 'Were you thanked when you checked out?',
            'options' => [
                'yes' => 'Yes',
                'no' => 'No',
                'dont_remember' => "Don't remember",
            ],
        ],

        'purchase_intent' => [
            'type' => 'single_choice',
            'label' => 'Today, I purchased…',
            'options' => [
                'more_than_intended' => 'More than I intended',
                'as_much_as_intended' => 'As much as I intended',
                'less_than_intended' => 'Less than I intended',
                'nothing' => 'Nothing at all',
            ],
        ],

        'purchase_barrier' => [
            'type' => 'multi_select_other',
            'label' => 'What stopped you?',
            'condition' => ['purchase_intent' => ['less_than_intended', 'nothing']],
            'options' => $barrierOptions,
        ],

        'staff_greet' => [
            'type' => 'likert',
            'preamble' => 'During my last visit…',
            'label' => 'I was greeted by a staff member.',
        ],

        'look_first' => [
            'type' => 'single_choice',
            'label' => 'When you need something we carry, where do you usually look first?',
            'options' => [
                'our_stores' => 'Sports Center or HeatWave',
                'another_nassau_store' => 'Another store in Nassau',
                'order_online_us' => 'Order online from the US',
                'wait_travel_abroad' => "Wait until I'm travelling abroad",
            ],
        ],

        'usual_barrier' => [
            'type' => 'multi_select_other',
            'label' => "When you've left one of our stores without buying, what usually stopped you?",
            'options' => $barrierOptions,
        ],

        'product_request' => [
            'type' => 'text',
            'optional' => true,
            'label' => "Is there a product you'd like us to carry that we don't?",
        ],

        'improvement' => [
            'type' => 'text',
            'optional' => true,
            'label' => 'What could we do to improve your experience?',
        ],

        'contact' => [
            'type' => 'contact',
            'optional' => true,
            'label' => "Leave your name and email to enter this month's gift card draw.",
            'follow_up_label' => "I'd like someone to follow up with me about my experience.",
        ],

    ],

    'sequences' => [

        'qr' => [
            'departments',
            'overall_score',
            'found_what_looking_for',
            'staff_knowledgeable',
            'staff_helpful',
            'store_clean',
            'prices_fair',
            'greeted',
            'thanked_at_checkout',
            'purchase_intent',
            'purchase_barrier',
            'look_first',
            'shop_frequency',
            'product_request',
            'improvement',
            'contact',
        ],

        'email' => [
            'primary_location',
            'departments',
            'last_shopped',
            'shop_frequency',
            'overall_score',
            'found_what_looking_for',
            'staff_knowledgeable',
            'staff_helpful',
            'store_clean',
            'prices_fair',
            'staff_greet',
            'look_first',
            'usual_barrier',
            'product_request',
            'improvement',
            'contact',
        ],

    ],

];
