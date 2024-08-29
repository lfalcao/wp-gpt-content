<?php

$prompt = "Review the following content to improve SEO, link it with existing tags and categories, and suggest tags and categories for the article: ";
$api_key = "sk-....";
$max_tokens = 5000;

// model must support structured output
$model = 'gpt-4o-mini';

function reviewContent($content) {
  global $api_key, $model, $max_tokens, $prompt;

  $curl = curl_init();

  curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    // CURLOPT_VERBOSE => true,
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json",
      "Authorization: Bearer ${api_key}"
    ],
    CURLOPT_POSTFIELDS => json_encode([
      "model" => $model,
      "messages" => [[ 
        "role"=> "user",
        "content" => $prompt . $content
      ]],
      "max_tokens" => $max_tokens,
      "temperature" => 0.7,
      "response_format" => [
      "type" => "json_schema",
      "json_schema" => [
        "name" => "post_content",
        "schema" => [
          "type" => "object",
          "properties" => [
            "content" => [
              "type" => "string"
            ],
            "categories" => [
              "type" => "array",
              "items" => [
                "type" => "string"
              ]
            ],
            "tags" => [
              "type" => "array",
              "items" => [
                "type" => "string"
              ]
            ]
          ],
          "required" => ["content", "categories", "tags"],
          "additionalProperties" => false
        ],
        "strict" => true
      ]
      ]
    ]),
  ]);

  $response = curl_exec($curl);
  curl_close($curl);

  $result = json_decode($response, true);

  return $result['choices'][0]['message']['content'];
}

function updatePost($post_id, $new_content, $new_tags, $new_categories) {
  wp_set_post_categories($post_id, []);
  wp_set_post_tags($post_id, []);

  if (!empty($new_categories)) {
    $category_ids = [];
    foreach ($new_categories as $category_name) {
      $category = get_term_by('name', $category_name, 'category');

      if (!$category) {
        $category_id = wp_create_category($category_name);
      } else {
        $category_id = $category->term_id;
      }

      $category_ids[] = $category_id;
    }

    wp_set_post_categories($post_id, $category_ids);
  }

  if (!empty($new_tags)) {
    wp_set_post_tags($post_id, $new_tags);
  }

  if (!empty($new_content)) {
    wp_update_post([
      'ID' => $post_id,
      'post_content' => $new_content,
    ]);
  }
}

function debug_message($post, $data){
  $tags = wp_get_post_tags((int)$post->ID);
  $tag_names = array_map(function($tag) {
      return $tag->name;
  }, $tags);

  $category_ids = wp_get_post_categories($post->ID);
  $category_names = [];

  foreach ($category_ids as $cat_id) {
    $category_names[] = get_cat_name($cat_id);
  }

  echo "-------------------------------------------------- \n"; 
  echo "Post title: " . $post->post_title . "\n";
  echo "Tags: \n";
  echo "Current: " . implode(',', $tag_names) . "\n";
  echo "New: " . implode(', ',  $data['tags']) . "\n";

  echo "Categories:\n";
  echo "Current: " . implode(', ', $category_names) . "\n";
  echo "New: " . implode(', ', $data['categories']) . "\n" ;
  
  echo "Content: \n";  
  echo "Current: " . $post->post_content . "\n";
  echo "New: " . $data['content'] . "\n";
}

$posts = get_posts([
  'numberposts' => -1, # get all posts
  'post_type' => 'post',
  'post_status' => 'publish',
]);

foreach ($posts as $post) {
  echo "Processing Post: " . $post->ID . "\n";
  ob_flush(); flush();

  $content = reviewContent($post->post_content);
  $data = json_decode($content, true);

  if (isset($_ENV['DEBUG'])) {
    debug_message($post, $data);  
    ob_flush(); flush();
    die();
  }

  updatePost($post->ID, $data['content'], $data['tags'], $data['categories']);
}

echo "Posts updated and revised.";
