<?php

namespace App\Controller;

use Exception;
use Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UrlParserFormType;

class IndexController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(UrlParserFormType::class);
        $form->handleRequest($request);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        if ($form->isSubmitted() && $form->get('save')->isClicked()) {
            if ($form->isValid()) {
                $status = false;

                try {
                    $parseResult = new Parser($this->client, $form->get('linkUrl')->getData());
                    if ($parseResult->getStatus() === false) {
                        $form->addError(new FormError($parseResult->getError()));
                    } else {
                        $status = true;
                    }
                } catch (Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

                if ($status) {
                    return $this->render('index/grid.html.twig', [
                        'form' => $form->createView(),
                    ]);
                } else {
                    return $this->render('index/index.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            } else {
                $form->addError(new FormError('Форма заполнена некорректно'));
                return $this->render('index/index.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        } else {
            return $this->render('index/index.html.twig', [
                'form' => $form->createView(),

            ]);
        }
    }

}
