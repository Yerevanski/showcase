<?php

namespace Drupal\showcase\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ShowcaseController extends ControllerBase {

  public function getShowcase(Request $request):JsonResponse {

    $data = [];

    $allowedKey = ['showcase'];

    foreach ($request->query->keys() as $key) {
      if(!in_array($key, $allowedKey)) {
        return new JsonResponse(['error' => 'Invalid Parameter', 400]);
      }
    }

    $nid = $request->get('showcase');

    $node = Node::load($nid);


    if (is_null($node)) {
      return new JsonResponse(['notice'=>'Node is not exist'], 200);
    }

    $type = $node->getType();

    if ($type != 'showcase') {
      return new JsonResponse(['notice'=>'Node is not showcase type'], 200);
    }

    $data['title'] = $node->getTitle();
    $data['short_description'] = $node->get('field_short_description')->getvalue();
    $data['description'] = $node->get('field_description')->getvalue();
    $data['address'] = $node->get('field_address')->getvalue();

    $fid = $node->get('field_featured_image')->entity->id();

    $data['featured_image']['path'] = $this->convertImage($fid);
    $data['featured_image']['alt'] = $node->get('field_featured_image')->alt;

    $lid = $node->get('field_logo_image')->entity->id();

    $data['logo_image']['path'] = $this->convertImage($lid);
    $data['logo_image']['alt'] = $node->get('field_logo_image')->alt;

    $data['facebook'] = $node->get('field_facebook_url')->getvalue();
    $data['twitter'] = $node->get('field_twitter_url')->getvalue();

    $linked_article_id = $node->get('field_referenced_article')->getvalue()[0]['target_id'];

    $data['linked_article'] = $this->getReferencedArticle($linked_article_id);
    $data['featured'] = $node->get('field_featured')->getvalue();

    return new JsonResponse($data);
  }

  public function getShowcases(Request $request):JsonResponse {

    $data = [];

    $key = \Drupal::request()->query->get('featured');

    if ($key == 1) {
      $nids = \Drupal::entityQuery('node')
        ->condition('type','showcase')
        ->condition('field_featured', 1)
        ->addTag('random')
        ->range(0, 3)
        ->execute();
    }
    else {
      $nids = \Drupal::entityQuery('node')
        ->condition('type','showcase')
        ->condition('field_featured', 0)
        ->addTag('random')
        ->range(0, 3)
        ->execute();
    }


    $nodes =  Node::loadMultiple($nids);

    foreach ($nodes as $node) {

      $fid = $node->get('field_featured_image')->entity->id();

      $linked_article_id =  $node->get('field_referenced_article')->getValue()[0]['target_id'];
      if($linked_article_id) {
        $linked_article = $this->getReferencedArticle($linked_article_id);
      }

      $data[] = [
        'id' => $node->id(),
        'title' => $node->getTitle(),
        'featured_image' => [
          'path' => $this->convertImage($fid),
          'alt' => $node->get('field_featured_image')->alt
        ],
        'linked_post' =>  $linked_article,
      ];

    }

    return new JsonResponse($data);
  }

  public function convertImage(int $targetId):string {

    $image = file_create_url(File::load($targetId)->getFileUri());
    return $image;
  }

  public function getReferencedArticle($targetId) : array {

    $referenced_article_node = Node::load($targetId);
    $referenced_article['title'] = $referenced_article_node->getTitle();
    $referenced_article['path'] = $referenced_article_node->toUrl()
      ->toString();

    return $referenced_article;
  }

}
