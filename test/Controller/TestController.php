<?php

namespace Sylviavdv\Router\Tests\Controller;

use Sylviavdv\Router\Attributes\Route;

class TestController
{
    #[Route('index', method: 'GET')]
    public function index(): void { echo __FUNCTION__; }

    #[Route('/users', method: 'GET')]
    public function users(): void { echo __FUNCTION__; }

    #[Route('/users/{id}', method: 'GET')]
    public function show(int $id): void { echo __FUNCTION__.' '.$id; }

    #[Route('/test/{id}', params: ['id' => '\d+'])]
    public function withParams(string $id): void { echo __FUNCTION__.' '.$id; }

    #[Route('/users', method: 'POST')]
    public function create(): void { echo __FUNCTION__; }

    #[Route('([a-z]{2}/)?blog/tag/{tag}', method: 'get', params: ['tag' => '[a-zA-Z0-9_-]+'])]
    public function tag(string $tag): void { echo __FUNCTION__.' '.$tag; }

    #[Route('/search', getRequirements: ['q' => '.+'])]
    public function search(string $q): void { echo __FUNCTION__.' '.$q; }

    #[Route('/login', method: 'POST', postRequirements: ['password' => '.{8,}'])]
    public function login(): void { echo __FUNCTION__; }

    #[Route('/', name: 'default')]
    #[Route('/home', name: 'home')]
    public function home(Route $route): void { echo __FUNCTION__.' '.$route->name; }

    #[Route('/files/{name}', priority: 10)]
    public function file(): void { echo __FUNCTION__; }

    #[Route('/{slug}', method:'GET', priority: 0)]
    public function page(): void { echo __FUNCTION__; }

    #[Route('view', method: 'GET', meta: ['for_admins' => true])]
    public function adminView(): void { echo __FUNCTION__; }

    #[Route('view', method: 'GET', meta: ['for_admins' => false])]
    public function userView(): void { echo __FUNCTION__; }
}
