<?php

namespace App\Controller;

use Manao\Bitrix\Rest\Client\Client;
use Manao\Bitrix\Rest\Client\RestMethod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Service\AppService;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Request $request, AppService $appService): Response
    {   
        $data = $appService->getData($request);
        return $this->render('app/index.html.twig', $data); 
    }

    #[Route('/ajax', name: 'app_ajax')]
    public function ajax(Request $request, AppService $appService): Response
    {
        $data = $appService->getData($request);
        return $this->render('app/table.html.twig', $data); 
    }
}
