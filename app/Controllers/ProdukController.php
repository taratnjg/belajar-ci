<?php

namespace App\Controllers;

namespace App\Controllers;

use App\Models\ProductModel;
use Dompdf\Dompdf;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ProdukController extends BaseController
{
    protected $product;
    protected $validation;

    function __construct()
    {
        $this->product = new ProductModel();
    }


    public function index()
    {
        $product = $this->product->findAll();
        $data['product'] = $product;

        return view('v_produk', $data);
    }

    public function create()
    {
        $dataFoto = $this->request->getFile('foto');

        $dataForm = [
            'nama' => $this->request->getPost('nama'),
            'harga' => $this->request->getPost('harga'),
            'jumlah' => $this->request->getPost('jumlah'),
            'created_at' => date("Y-m-d H:i:s")
        ];

                // Validation rules
        $rules = [
            'nama' => 'required|min_length[6]',
            'harga' => 'required|numeric',
            'jumlah' => 'required|numeric'
        ];  
        
        // Validate the input
        if ($this->validate($rules)) {

        if ($dataFoto->isValid()) {
            $fileName = $dataFoto->getRandomName();
            $dataForm['foto'] = $fileName;
            $dataFoto->move('img/', $fileName);
        }

        $this->product->insert($dataForm);

        return redirect('produk')->with('success', 'Data Berhasil Ditambah');
        } else {
                    // Validation failed, show errors
                session()->setFlashdata('failed', $this->validator->listErrors());
                return redirect()->back();
            }
    }

    public function edit($id)
    {
        $dataProduk = $this->product->find($id);

        $dataForm = [
            'nama' => $this->request->getPost('nama'),
            'harga' => $this->request->getPost('harga'),
            'jumlah' => $this->request->getPost('jumlah'),
            'updated_at' => date("Y-m-d H:i:s")
        ];

        // Validation rules
        $rules = [
            'nama' => 'required|min_length[6]',
            'harga' => 'required|numeric',
            'jumlah' => 'required|numeric'
        ];  

        // Validate the input
        if ($this->validate($rules)) {
            if ($this->request->getPost('check') == 1) {
                if ($dataProduk['foto'] != '' and file_exists("img/" . $dataProduk['foto'] . "")) {
                    unlink("img/" . $dataProduk['foto']);
                }

                $dataFoto = $this->request->getFile('foto');

                if ($dataFoto->isValid()) {
                    $fileName = $dataFoto->getRandomName();
                    $dataFoto->move('img/', $fileName);
                    $dataForm['foto'] = $fileName;
                }
            }

            $this->product->update($id, $dataForm);

            return redirect('produk')->with('success', 'Data Berhasil Diubah');
        } else {
                    // Validation failed, show errors
                session()->setFlashdata('failed', $this->validator->listErrors());
                return redirect()->back();
            }
    }

    public function delete($id)
    {
        $dataProduk = $this->product->find($id);

        if ($dataProduk['foto'] != '' and file_exists("img/" . $dataProduk['foto'] . "")) {
            unlink("img/" . $dataProduk['foto']);
        }

        $this->product->delete($id);

        return redirect('produk')->with('success', 'Data Berhasil Dihapus');
    }

    public function download()
    {
        $product = $this->product->findAll();

        $html = view('v_produkPDF', ['product' => $product]);

        $filename = date('y-m-d-H-i-s') . '-produk';

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
