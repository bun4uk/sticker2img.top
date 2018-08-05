<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 8/5/18
 * Time: 8:26 PM
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function index(Request $request)
    {
        file_put_contents('request_dump', $request);
        return new Response('yes');
    }

}