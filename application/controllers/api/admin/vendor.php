<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/smsgateway/autoload.php';
use SMSGatewayMe\Client\ApiClient;
use SMSGatewayMe\Client\Configuration;
use SMSGatewayMe\Client\Api\MessageApi;
use SMSGatewayMe\Client\Model\SendMessageRequest;

header('Access-Control-Allow-Origin: *');

class Vendor extends REST_Controller {

    public function __construct($config='rest') 
    {
        parent::__construct($config);
        $this->load->library('session');
        $this->load->helper('crypt');
        $this->load->helper('jwt');
        $this->load->model('M_user');
        $this->load->model('M_vendor');
        
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index_get()
    {
        $token = $this->get('token');
        $vendor_id = $this->get('vendor_id');

        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
        ];

        $data = $this->get();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array('id' => $vendor_id);
                if (empty($vendor_id)) {
                    $return['vendor'] = $this->M_vendor->where('vendor', $data);
                }else{
                    $return['vendor'] = array($this->M_vendor->where('vendor', $data));

                }
            }
        }
        return $this->set_response($return, REST_Controller::HTTP_OK); 
    }

    public function edit_post()
    {
        $token = $this->post('token');
        $email = $this->post('email');
        $nama_vendor = $this->post('nama_vendor');
        $id = $this->post('id');
        $nomor_hp = $this->post('nomor_hp');

        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
            [
                'field' => 'email',
                'label' => 'Email',
                'rules' => 'valid_email|required',
                'errors' => [
                    'valid_email' => '%s Tidak Valid!',
                    'required' => '%s Dibutuhkan!',
                ],
            ],
            [
                'field' => 'nomor_hp',
                'label' => 'Nomor Handphone',
                'rules' => 'numeric|required',
                'errors' => [
                    'numeric' => '%s Tidak Valid!',
                    'required' => '%s Dibutuhkan!',
                ],
            ],
        ];

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array(
                    'email' => $email, 
                    'nomor_hp' => $nomor_hp, 
                    'nama_vendor' => $nama_vendor, 
                );
                $key = array('id' => $id);
                $update = $this->M_vendor->update('vendor', $key, $data);
                if ($update) {
                    $output['message'] = 'sukses update';
                    $code = REST_Controller::HTTP_OK;
                }else{
                    $output['message'] = 'gagal update';
                    $code = REST_Controller::HTTP_BAD_REQUEST;
                }
            }else{
                return false;
            }
            $this->set_response($output, $code);
        }

    }

    public function delete_post()
    {
        $token = $this->post('token');
        $id = $this->post('id');

        $config = [
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
        ];
        
        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                
                $key = array('id' => $id);
                $update = $this->M_vendor->delete('vendor', $key);
                if ($update) {
                    $output['message'] = 'sukses delete';
                    $code = REST_Controller::HTTP_OK;
                }else{
                    $output['message'] = 'gagal delete';
                    $code = REST_Controller::HTTP_BAD_REQUEST;
                }
            }else{
                return false;
            }
            $this->set_response($output, $code);
        }
    }

    public function index_post()
    {
        $token = $this->post('token');
        $nama_vendor = $this->post('nama_vendor');
        $email = $this->post('email');
        $nomor_hp = $this->post('nomor_hp');
        $alamat = $this->post('alamat');

        $config = [
            [
                'field' => 'email',
                'label' => 'Email',
                'rules' => 'required|valid_email|is_unique[vendor.email]|max_length[256]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'valid_email' => '%s Tidak Valid',
                    'is_unique' => '%s Sudah digunakan',
                    'max_length' => '%s Kelebihan karakter',
                ],
            ],
            [
                'field' => 'token',
                'label' => 'Token',
                'rules' => 'required',
                'errors' => [
                    'required' => '%s Diperlukan',
                ],
            ],
            [
                'field' => 'nomor_hp',
                'label' => 'Nomor HP',
                'rules' => 'required|max_length[13]|min_length[10]|is_unique[vendor.nomor_hp]',
                'errors' => [
                    'required' => '%s Diperlukan',
                    'is_unique' => '%s Telah Digunakan',
                    'max_length' => '%s Kelebihan karakter',
                    'min_length' => '%s Kekurangan karakter',
                ],
            ],
        ];

        $data = $this->post();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($config);
        
        if($this->form_validation->run()==FALSE){
            $output = $this->form_validation->error_array();
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }else{
            $validation['JWT'] = $this->_decrypt($token);
            if ($validation['JWT']->status == 'admin') {
                $data = array(
                    'nama_vendor' => $nama_vendor,
                    'email' => $email,
                    'nomor_hp' => $nomor_hp,
                    'alamat' => $alamat,
                    'created_at' => date('Y-m-d h:i:s'),
                    'updated_at' => date('Y-m-d h:i:s'),
                );
                if($this->M_user->create('vendor', $data)) {
                    $validation['message'] = 'Sukses Insert Vendor';
                }else{
                    $validation['message'] = 'Gagal Insert Vendor';
                }
            }else{
                return false;
            }
            return $this->set_response($validation, REST_Controller::HTTP_OK); 
        }
    }

    public function _decrypt($token)
    {
        $CI =& get_instance();
        $decrypt = array(
            'n'     => $CI->config->item('key_rsa'), 
            'd'     => $CI->config->item('key_d'), 
            'token' => $token
        );
        $validation = array();
        $validation['JWT'] = JWT::validateTimestamp(Crypt::decrypt_($decrypt), 10000000);
        return $validation['JWT'];
    }

    public function cek_vendor($key)
    {
        $data = array('id' => $key);
        $cek = $this->M_user->_isNotExists($data, 'vendor');
        if($cek){
            return false;
        } else{
            return true;
        }
    }


}