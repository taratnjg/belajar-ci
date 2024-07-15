<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;
use CodeIgniter\HTTP\ResponseInterface;

use Dompdf\Dompdf;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $url = "https://api.rajaongkir.com/starter/";
    protected $apiKey = "8a8073141ddb4ace76ee20437bbd4dd7";
    protected $transaction;
    protected $transaction_detail;

    function __construct()
    {
        helper('number');
        helper('form');
        $this->cart = \Config\Services::cart();
        $this->transaction = new TransactionModel();
        $this->transaction_detail = new TransactionDetailModel();
    }

    public function index()
    {
        $data['items'] = $this->cart->contents();
        $data['total'] = $this->cart->total();
        return view('v_keranjang', $data);
    }

    public function cart_add()
    {
        $this->cart->insert(array(
            'id'        => $this->request->getPost('id'),
            'qty'       => 1,
            'price'     => $this->request->getPost('harga'),
            'name'      => $this->request->getPost('nama'),
            'options'   => array('foto' => $this->request->getPost('foto'))
        ));
        session()->setflashdata('success', 'Produk berhasil ditambahkan ke keranjang. (<a href="' . base_url() . 'keranjang">Lihat</a>)');
        return redirect()->to(base_url('/'));
    }

    public function cart_clear()
    {
        $this->cart->destroy();
        session()->setflashdata('success', 'Keranjang Berhasil Dikosongkan');
        return redirect()->to(base_url('keranjang'));
    }

    public function cart_edit()
    {
        $i = 1;
        foreach ($this->cart->contents() as $value) {
            $this->cart->update(array(
                'rowid' => $value['rowid'],
                'qty'   => $this->request->getPost('qty' . $i++)
            ));
        }

        session()->setflashdata('success', 'Keranjang Berhasil Diedit');
        return redirect()->to(base_url('keranjang'));
    }

    public function cart_delete($rowid)
    {
        $this->cart->remove($rowid);
        session()->setflashdata('success', 'Keranjang Berhasil Dihapus');
        return redirect()->to(base_url('keranjang'));
    }

    public function checkout()
    {
        $data['items'] = $this->cart->contents();
        $data['total'] = $this->cart->total();
        
        $provinsi = $this->rajaongkir('province');
        
        if ($provinsi) {
            $decodedProvinsi = json_decode($provinsi);
            if (isset($decodedProvinsi->rajaongkir->results)) {
                $data['provinsi'] = $decodedProvinsi->rajaongkir->results;
            } else {
                $data['provinsi'] = []; // Set to an empty array or handle the error as needed
                log_message('error', 'Invalid response structure from Rajaongkir API');
            }
        } else {
            $data['provinsi'] = []; // Set to an empty array or handle the error as needed
            log_message('error', 'Failed to fetch data from Rajaongkir API');
        }

        return view('v_checkout', $data);
    }


    public function getCity()
    {
        if ($this->request->isAJAX()) {
            $id_province = $this->request->getGet('id_province');
            $data = $this->rajaongkir('city', $id_province);
            return $this->response->setJSON($data);
        }
    }

    public function getCost()
    {
        if ($this->request->isAJAX()) {
            $origin = $this->request->getGet('origin');
            $destination = $this->request->getGet('destination');
            $weight = $this->request->getGet('weight');
            $courier = $this->request->getGet('courier');
            $data = $this->rajaongkircost($origin, $destination, $weight, $courier);
            return $this->response->setJSON($data);
        }
    }

    private function rajaongkircost($origin, $destination, $weight, $courier)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "origin=" . $origin . "&destination=" . $destination . "&weight=" . $weight . "&courier=" . $courier,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                "key: " . $this->apiKey,
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        return $response;
    }


    private function rajaongkir($method, $id_province = null)
    {
        $endPoint = $this->url . $method;

        if ($id_province != null) {
            $endPoint = $endPoint . "?province=" . $id_province;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endPoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: " . $this->apiKey
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_message('error', 'Curl Error: ' . $err);
            return null;
        }

        return $response;
    }


    public function buy()
    {
        if ($this->request->getPost()) {
            $dataForm = [
                'username' => $this->request->getPost('username'),
                'total_harga' => $this->request->getPost('total_harga'),
                'alamat' => $this->request->getPost('alamat'),
                'ongkir' => $this->request->getPost('ongkir'),
                'status' => 0,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];

            $this->transaction->insert($dataForm);

            $last_insert_id = $this->transaction->getInsertID();

            foreach ($this->cart->contents() as $value) {
                $dataFormDetail = [
                    'transaction_id' => $last_insert_id,
                    'product_id' => $value['id'],
                    'jumlah' => $value['qty'],
                    'diskon' => 0,
                    'subtotal_harga' => $value['qty'] * $value['price'],
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];

                $this->transaction_detail->insert($dataFormDetail);
            }

            $this->cart->destroy();

            return redirect()->to(base_url('profile'));
        }
    }

public function transaksi()
    {
        $data['transactions'] = $this->transaction->findAll();
        return view('v_transaksi', $data);
    }

   public function updateStatus()
{
    $transactionId = $this->request->getPost('id');
    $status = $this->request->getPost('status');

    if ($transactionId && ($status === '0' || $status === '1')) {
        $this->transaction->update($transactionId, ['status' => $status]);
        session()->setFlashData('success', 'Data Berhasil Diubah');
    } else {
        session()->setFlashData('failed', 'Invalid request');
    }

    return redirect()->to(base_url('transaksi'));
}

public function downloadTransaksi()
    {
        $transaction = $this->transaction->findAll();

        $html = view('v_transaksiPDF', ['transaction' => $transaction]);

        $filename = date('y-m-d-H-i-s') . '-transaksi';

        // instantiate and use the dompdf class
        $dompdf = new Dompdf();

        // load HTML content
        $dompdf->loadHtml($html);

        // (optional) setup the paper size and orientation
        $dompdf->setPaper('A4', 'potrait');

        // render html as PDF
        $dompdf->render();

        // output the generated pdf
        $dompdf->stream($filename);
    }


}
