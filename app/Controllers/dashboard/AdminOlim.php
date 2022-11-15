<?php

namespace App\Controllers\dashboard;

use App\Controllers\BaseController;

use App\Models\OlimModel;
use App\Models\UserModel;

class AdminOlim extends BaseController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->db = \Config\Database::connect();
        $this->Olim_Model = new OlimModel();
        $this->User_Model = new UserModel();
    }

    public function is_pass_same($pass)
    {
        $builder = $this->db->table('user');
        $builder->where('user_password', $pass);
        return !empty($builder->get()->getResult());
    }

    public function delete_file($path, $filename)
    {
        if (!empty($filename))
            unlink($path . $filename);
        return;
    }

    public function konfirmasi_team()
    {
        $data =
            [
                'title' => 'Admin Olimpiade',
                'nama' => 'Admin Olimpiade',
                'event' => 'Olimpiade',
                'step' => 'Konfirmasi',
                'team_list' => $this->Olim_Model->where('olim_status', 0)->findAll(),
                'terkonfirmasi' => $this->Olim_Model->where('olim_status', 1)->countAllResults(),
                'belum_terkonfirmasi' => $this->Olim_Model->where('olim_status', 0)->countAllResults(),
                'total_team' => $this->Olim_Model->where('olim_status', 0)->countAllResults() + $this->Olim_Model->where('olim_status', 1)->countAllResults(),
            ];
        return view('landing/pages/dashboard/admin/olim/konfirmasi_team', $data);
    }

    public function confirmed_team()
    {
        $data =
            [
                'title' => 'Admin Olimpiade',
                'nama' => 'Admin Olimpiade',
                'event' => 'Olimpiade',
                'step' => 'List',
                'team_list' => $this->Olim_Model->where('olim_status', 1)->findAll(),
                'terkonfirmasi' => $this->Olim_Model->where('olim_status', 1)->countAllResults(),
                'belum_terkonfirmasi' => $this->Olim_Model->where('olim_status', 0)->countAllResults(),
                'total_team' => $this->Olim_Model->where('olim_status', 0)->countAllResults() + $this->Olim_Model->where('olim_status', 1)->countAllResults(),
            ];
        return view('landing/pages/dashboard/admin/olim/list_team', $data);
    }

    public function verify_konfirmasi_team($id, $status)
    {
        helper('text');
        $team = $this->Olim_Model->where('olim_tim_id', $id)->first();
        if ($status) {
            $password = random_string('special_alnum', 12); //special_alnum dari function random_string di text_helper.php (nambah sendiri)

            if ($this->is_pass_same($password)) {
                return redirect()->to('dashboard/admin-olim/verify-konfirmasi-team/' . $id . '/1');
            } else {
                $data_status = [
                    'olim_tim_id' => $team['olim_tim_id'],
                    'olim_status' => 1
                ];
                $this->Olim_Model->save($data_status);
                $data = [
                    'user_lomba'       => 'olim',
                    'user_tim_nama'  => $team['olim_tim_nama'],
                    'user_username'    => 'olimpiade' . '_' . $team['olim_tim_nama'],
                    'user_password'    => password_hash($password, PASSWORD_DEFAULT)
                ];
                $this->User_Model->save($data);
                $subject = "[Accepted] Olimpiade";
                $message = "Halo {$team['olim_tim_nama']} dari {$team['olim_asal_institusi']},<br>
                  <br>
                  Terima kasih sudah mendaftar pada event kami, \"Olimpiade.\"<br>
                  <br>
                  Berikut adalah Username dan Password anda: <br>
                  <br>
                  Username : {$data['user_username']}<br>
                  Password : {$password}<br><br>
                  <br>
                  --<br>
                  Salam Hormat,<br>
                  <br>
                  A Renewal Agents 4.0";
                $this->sendEmail($team['olim_email_ketua'], $subject, $message);
                $this->session->setFlashdata('msg', 'Berhasil Menerima Tim: ' . $team['olim_tim_nama']);
            }
        } else {
            $kp_path = 'uploads/olim/suket/';
            $ig_path = 'uploads/olim/ig_ara/';
            $tt_path = 'uploads/olim/tiktok/';
            $bukti_bayar_path = 'uploads/olim/bukti_bayar/';

            $this->delete_file($kp_path, $team['olim_kp_surket_ketua']);
            $this->delete_file($kp_path, $team['olim_kp_surket_anggota_1']);

            $this->delete_file($ig_path, $team['olim_ig_ara_ketua']);
            $this->delete_file($ig_path, $team['olim_ig_ara_anggota_1']);

            $this->delete_file($tt_path, $team['olim_tiktok_ketua']);
            $this->delete_file($tt_path, $team['olim_tiktok_anggota_1']);

            $this->delete_file($bukti_bayar_path, $team['olim_bukti_bayar']);

            $this->Olim_Model->where('olim_tim_id', $id)->delete();
            $subject = "[Rejected] Olimpiade";
            $message = "Halo {$team['olim_tim_nama']} dari {$team['olim_asal_institusi']},<br>
      <br>
      Terima kasih sudah mendaftar pada event kami, \"Olimpiade.\"<br>
      <br>
      Mohon maaf, persyaratan yang anda kirimkan tidak cukup. Mohon mendaftar kembali di halaman registrasi.<br>
      <br>
      <br>
      --<br>
      Salam Hormat,<br>
      <br>
      A Renewal Agents 4.0";
            $this->sendEmail($team['olim_email_ketua'], $subject, $message);
            $this->session->setFlashdata('msg', 'Berhasil Menolak Tim: ' . $team['olim_tim_nama']);
        }
        return redirect()->to('dashboard/admin-olim/konfirmasi-team');
    }
}