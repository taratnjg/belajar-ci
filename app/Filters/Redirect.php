<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Redirect implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Do something here
        if (!session()->has('isLoggedIn')) {
            return redirect()->to(site_url('login'));
        } else if (session()->has('redirectContact') == TRUE) {
            session()->remove(['redirectContact']);
            return redirect()->to(base_url('/contact'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}
