<?php
/*   ________________________________________
    |                 GarudaCBT              |
    |    https://github.com/garudacbt/cbt    |
    |________________________________________|
*/
 defined("BASEPATH") or exit("No direct script access allowed"); class Userguru extends CI_Controller { public function __construct() { goto xEbSV; iguDl: show_error("Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href=\"" . base_url("dashboard") . "\">Kembali ke menu awal</a>", 403, "Akses Terlarang"); goto iNfro; iNfro: xSm2T: goto Dp7ee; xEbSV: parent::__construct(); goto UhOLC; lqMCV: if (!(!$this->ion_auth->is_admin() && !$this->ion_auth->in_group("guru"))) { goto xSm2T; } goto iguDl; JDmVU: $this->load->model("Dashboard_model", "dashboard"); goto qkPBP; X76T2: redirect("auth"); goto Ac7M3; fN_sU: $this->load->library(["datatables", "form_validation"]); goto Zl04F; Zl04F: $this->load->model("Users_model", "users"); goto H8tKh; Dp7ee: goto yk9wC; goto UejPj; UhOLC: if (!$this->ion_auth->logged_in()) { goto F0xpT; } goto lqMCV; H8tKh: $this->load->model("Master_model", "master"); goto JDmVU; UejPj: F0xpT: goto X76T2; qkPBP: $this->form_validation->set_error_delimiters('', ''); goto JKU1U; Ac7M3: yk9wC: goto fN_sU; JKU1U: } public function output_json($data, $encode = true) { goto rw17z; plncm: $this->output->set_content_type("application/json")->set_output($data); goto p6s31; rw17z: if (!$encode) { goto ZAYwS; } goto h4sc2; VDqKb: ZAYwS: goto plncm; h4sc2: $data = json_encode($data); goto VDqKb; p6s31: } public function data() { goto pKi02; uWZuL: $this->output_json($this->users->getUserGuru($tp->id_tp, $smt->id_smt), false); goto d7EWE; PY_NW: $smt = $this->dashboard->getSemesterActive(); goto uWZuL; pKi02: $tp = $this->dashboard->getTahunActive(); goto PY_NW; d7EWE: } public function index() { goto djXsB; uUU9a: $this->load->view("_templates/dashboard/_footer"); goto PCWD5; oSziB: $data["tp_active"] = $this->dashboard->getTahunActive(); goto aqBhJ; aqBhJ: $data["smt"] = $this->dashboard->getSemester(); goto SnjSS; P6kqx: $id = $this->users->getGuruByUsername($user->username); goto Ha1LS; u0ivT: $group = $this->ion_auth->get_users_groups($user->id)->row()->name; goto Vf1i5; djXsB: $user = $this->ion_auth->user()->row(); goto u0ivT; PCWD5: R3s2b: goto JV7kZ; eqi1I: goto R3s2b; goto Tvie0; Tvie0: XKud4: goto GSDRF; Ha1LS: $this->edit($id->id_guru); goto eqi1I; rDVWB: $this->load->view("users/guru/data"); goto uUU9a; SnjSS: $data["smt_active"] = $this->dashboard->getSemesterActive(); goto ju51A; rcHSL: if ($group === "admin") { goto XKud4; } goto P6kqx; GSDRF: $data["tp"] = $this->dashboard->getTahun(); goto oSziB; ju51A: $this->load->view("_templates/dashboard/_header", $data); goto rDVWB; Vf1i5: $data = ["user" => $user, "judul" => "User Management", "subjudul" => "Data User Guru", "profile" => $this->dashboard->getProfileAdmin($user->id), "setting" => $this->dashboard->getSetting()]; goto rcHSL; JV7kZ: } public function activate($id) { goto c85Tu; rfF2T: $group = array("2"); goto YNnjh; YNnjh: if ($this->ion_auth->username_check($username)) { goto uUWmS; } goto EWli3; c85Tu: $guru = $this->users->getDataGuru($id); goto SpFm9; iAN4Z: uUWmS: goto Xweyg; xRHYf: goto NTsAO; goto ImSa0; RiNN3: $id_user = $this->ion_auth->register($username, $password, $email, $additional_data, $group); goto wCb6I; SOZuK: $password = trim($guru->password ?? ''); goto KMzwc; c5wgw: $data["pass"] = $password; goto T7mVB; HPdql: NTsAO: goto BL13p; eHu3F: $data = ["status" => false, "msg" => "Username " . $email . " tidak tersedia (sudah digunakan)."]; goto HPdql; AHCaa: $last_name = count($nama) > 2 ? $nama[1] : end($nama); goto SOT3D; E4WcI: $first_name = $nama[0]; goto AHCaa; KMzwc: $email = strtolower($guru->username ?? '') . "@guru.com"; goto B7g2k; BL13p: goto NYPH6; goto iAN4Z; op7ve: $this->db->set("id_user", $id_user); goto EJ9Sn; Xweyg: $data = ["status" => false, "msg" => "Username " . $username . " tidak tersedia (sudah digunakan)."]; goto mBzUl; wCb6I: $data = ["status" => true, "msg" => "Akun " . $guru->nama_guru . " diaktifkan."]; goto op7ve; SOT3D: $username = trim($guru->username ?? ''); goto SOZuK; T7mVB: $this->output_json($data); goto x0fnv; N0I7b: $this->db->update("master_guru"); goto xRHYf; EJ9Sn: $this->db->where("id_guru", $id); goto N0I7b; EWli3: if ($this->ion_auth->email_check($email)) { goto HVdI1; } goto RiNN3; ImSa0: HVdI1: goto eHu3F; mBzUl: NYPH6: goto c5wgw; SpFm9: $nama = explode(" ", $guru->nama_guru ?? ''); goto E4WcI; B7g2k: $additional_data = ["first_name" => $first_name, "last_name" => $last_name]; goto rfF2T; x0fnv: } public function deactivate($id = NULL) { goto gw2wl; GCak_: $data = ["status" => $deleted, "msg" => "telah dinonaktifkan."]; goto ZvfcX; MLIYc: $id = (int) $id; goto IfSxo; tTJL6: $data = ["status" => false, "msg" => "You must be an administrator to view this page."]; goto fvP6h; IfSxo: if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) { goto sjjhU; } goto CZPfY; CbhAZ: goto uOxxM; goto dotHo; Rawgo: $deleted = $this->ion_auth->delete_user($id); goto GCak_; dotHo: sjjhU: goto Rawgo; cuep2: QglTj: goto tTJL6; ZvfcX: uOxxM: goto TMhQw; fvP6h: qwKAB: goto w5xbm; gw2wl: if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) { goto QglTj; } goto MLIYc; CZPfY: $data = ["status" => false, "msg" => "Anda bukan admin."]; goto CbhAZ; w5xbm: $this->output_json($data); goto umKc2; TMhQw: goto qwKAB; goto cuep2; umKc2: } public function aktifkanSemua() { goto VGN45; Gvki2: foreach ($guruAktif as $guru) { goto SC7jR; paoq0: goto iJIJE; goto cVpb2; JMc6v: $this->activate($guru->id_guru); goto paoq0; JHxf6: $jum += 1; goto FStH0; cVpb2: S1r9c: goto njfKn; SC7jR: if ($guru->aktif > 0) { goto S1r9c; } goto JMc6v; gEXW7: iJIJE: goto JHxf6; njfKn: goto Tr17h; goto gEXW7; FStH0: Tr17h: goto PvY_x; PvY_x: } goto rbWfQ; bRRyt: $jum = 0; goto Gvki2; B04Gz: $data = ["status" => true, "jumlah" => $jum, "msg" => $jum . " Guru diaktifkan."]; goto memec; rbWfQ: TgWw5: goto B04Gz; memec: $this->output_json($data); goto NlQd7; VGN45: $guruAktif = $this->users->getGuruAktif(); goto bRRyt; NlQd7: } public function nonaktifkanSemua() { goto yX0k2; BkilN: foreach ($guruAktif as $guru) { goto vxhQ8; ghin_: goto MqG6u; goto ibmi2; jIDV1: $del = $this->deactivate($guru->id, ''); goto H82Kg; H82Kg: $this->output_json($del); goto t1YeN; t1YeN: MqG6u: goto uaYg_; loSPF: qEprt: goto iKyvj; vxhQ8: if ($guru->aktif > 0) { goto hUkx8; } goto qGpMu; uaYg_: $jum += 1; goto loSPF; ibmi2: hUkx8: goto jIDV1; qGpMu: goto qEprt; goto ghin_; iKyvj: } goto p5B8C; TrC2E: $jum = 0; goto BkilN; yX0k2: $guruAktif = $this->users->getGuruAktif(); goto TrC2E; AZR6i: $this->output_json($data); goto K5Lwv; p5B8C: oVCkn: goto S3hBH; S3hBH: $data = ["status" => true, "jumlah" => $jum, "msg" => $jum . " Guru dinonaktifkan."]; goto AZR6i; K5Lwv: } public function edit($id) { goto m4drO; f2KJ5: $data["smt_active"] = $smt; goto uZWuT; wdzbX: $this->load->view("_templates/dashboard/_header", $data); goto Kxig0; hqz55: $data["levels"] = $this->users->getLevelGuru(); goto wdzbX; qW_Im: goto FVF6w; goto BZHdO; BZHdO: yngqY: goto HWvfo; hl0gz: $users = $this->users->getUsers($guru->username); goto mV0Cf; ODY8k: $this->load->view("members/guru/templates/header", $data); goto GLzo7; M0k0T: $data["guru"] = $guru; goto SAhH6; bAO3y: $data["groups"] = $this->ion_auth->groups()->result(); goto r9XAX; r9XAX: $data["kelass"] = $this->users->getKelas($tp->id_tp, $smt->id_smt); goto mHAzC; Kxig0: $this->load->view("users/guru/edit"); goto lMB9e; ib2zN: $this->load->view("members/guru/templates/footer"); goto qW_Im; IhvTu: $data["tp_active"] = $tp; goto mPIKX; SAhH6: $data["tp"] = $this->dashboard->getTahun(); goto IhvTu; kQslX: $data["users"] = $users; goto M0k0T; APmxg: $smt = $this->dashboard->getSemesterActive(); goto jLAdU; PJ3Dk: if ($group === "admin") { goto yngqY; } goto ODY8k; mV0Cf: $user = $this->ion_auth->user()->row(); goto cvAVB; mPIKX: $data["smt"] = $this->dashboard->getSemester(); goto f2KJ5; cvAVB: $data = ["user" => $user, "judul" => "User Management", "subjudul" => "Edit Data User", "setting" => $this->dashboard->getSetting()]; goto kQslX; tnBgL: FVF6w: goto zCpop; jLAdU: $guru = $this->users->getDetailGuru($id); goto hl0gz; mHAzC: $data["mapels"] = $this->users->getMapel(); goto hqz55; m4drO: $tp = $this->dashboard->getTahunActive(); goto APmxg; GLzo7: $this->load->view("users/guru/edit"); goto ib2zN; uZWuT: $group = $this->ion_auth->get_users_groups($user->id)->row()->name; goto PJ3Dk; HWvfo: $data["profile"] = $this->dashboard->getProfileAdmin($user->id); goto bAO3y; lMB9e: $this->load->view("_templates/dashboard/_footer"); goto tnBgL; zCpop: } public function editLogin() {
    $id_guru = $this->input->post('id_guru', true);
    $username = trim((string) $this->input->post('username', true));
    $old = (string) $this->input->post('old', true);
    $password = trim((string) $this->input->post('new', true));
    $admin = $this->ion_auth->is_admin();

    $guru_target = $this->db->get_where('master_guru', array('id_guru' => $id_guru))->row();

    // Guru hanya boleh mengubah kredensialnya sendiri; admin boleh mengubah guru mana pun.
    if (!$admin) {
        $login = $this->ion_auth->user()->row();
        $guru_login = $login
            ? $this->db->get_where('master_guru', array('id_user' => $login->id))->row()
            : null;
        if (!$guru_login || (string) $guru_login->id_guru !== (string) $id_guru) {
            return $this->output_json(array('status' => false, 'text' => 'Akses ditolak.'));
        }
    }

    if (!$guru_target) {
        return $this->output_json(array('status' => false, 'text' => 'Data guru tidak ditemukan.'));
    }

    // Admin boleh mengganti password guru tanpa mengetahui password lama.
    // Guru yang mengganti passwordnya sendiri tetap wajib mengisi password lama.
    if (!$admin) {
        $this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
    }
    $this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[new_confirm]');
    $this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

    if ($this->form_validation->run() === FALSE) {
        return $this->output_json(array(
            'status' => false,
            'errors' => array(
                'old' => form_error('old'),
                'new' => form_error('new'),
                'new_confirm' => form_error('new_confirm'),
            ),
        ));
    }

    // Verifikasi kata sandi lama hanya untuk guru yang mengubah akunnya sendiri.
    if (!$admin) {
        $akun_target = $guru_target->id_user
            ? $this->db->get_where('users', array('id' => $guru_target->id_user))->row()
            : null;
        $sandi_valid = false;
        if ($akun_target && password_verify($old, $akun_target->password)) {
            $sandi_valid = true;
        } elseif (hash_equals((string) $guru_target->password, (string) $old)) {
            $sandi_valid = true;
        }
        if (!$sandi_valid) {
            return $this->output_json(array(
                'status' => false,
                'errors' => array('old' => 'Password lama tidak sesuai.'),
            ));
        }
    }

    $nama = explode(' ', (string) $guru_target->nama_guru);
    $first_name = $nama[0];
    $last_name = count($nama) > 1 ? end($nama) : '';
    $username = trim($username);
    $email = strtolower($username) . '@guru.com';

    $guru_lain = $this->master->getUserIdGuruByUsername($username);
    if ($guru_lain && (string) $guru_lain->id_guru !== (string) $id_guru) {
        return $this->output_json(array(
            'status' => false,
            'errors' => array('username' => 'Username sudah digunakan'),
        ));
    }

    $user_guru = $this->db->get_where('users', array('email' => $email))->row();
    $additional_data = array('first_name' => $first_name, 'last_name' => $last_name);
    $group = array('2');

    $this->db->trans_start();

    if ($user_guru && !$this->ion_auth->delete_user((int) $user_guru->id)) {
        $this->db->trans_complete();
        return $this->output_json(array('status' => false, 'text' => 'Gagal menghapus akun lama.'));
    }

    $id_user = $this->ion_auth->register($username, $password, $email, $additional_data, $group);
    if (!$id_user) {
        $this->db->trans_complete();
        return $this->output_json(array(
            'status' => false,
            'text' => 'Gagal membuat akun: ' . $this->ion_auth->errors(),
        ));
    }

    $update = $this->db->set('username', $username)
        ->set('password', $password)
        ->set('id_user', $id_user)
        ->where('id_guru', $id_guru)
        ->update('master_guru');

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE || !$update) {
        return $this->output_json(array('status' => false, 'text' => 'Gagal memperbarui data guru.'));
    }

    if (!$admin) {
        $this->session->sess_regenerate(true);
    }

    return $this->output_json(array('status' => true, 'text' => 'Update berhasil'));
}
function buangspasi($teks) { goto Xrtbu; gqgOP: UD5wr: goto Gxe39; d6oQ6: $remove[] = " "; goto qmBlG; GT_Jv: $hasil = $teks; goto DLI33; hoHgG: if (!strpos($teks, " ")) { goto UD5wr; } goto MPj1G; DLI33: cPAo3: goto hoHgG; MPj1G: $remove[] = "'"; goto xS1wW; KBB1Q: goto cPAo3; goto gqgOP; qmBlG: $hasil = str_replace($remove, '', $teks ?? ''); goto KBB1Q; Xrtbu: $teks = trim($teks ?? ''); goto GT_Jv; xS1wW: $remove[] = "."; goto d6oQ6; Gxe39: return $hasil; goto B1i8o; B1i8o: } private function registerGuru($username, $password, $email, $additional_data, $group) { goto ZYMXX; Gy_J_: $data["status"] = true; goto IHnho; s20Az: m7ytc: goto GGm2G; fMnm5: $data["status"] = false; goto s20Az; ZYMXX: $reg = $this->ion_auth->register($username, $password, $email, $additional_data, $group); goto Gy_J_; IHnho: $data["id"] = $reg; goto zli1n; GGm2G: return $data; goto tMqCL; zli1n: if (!($reg == false)) { goto m7ytc; } goto fMnm5; tMqCL: } public function reset_login() { goto tFOY4; zcwhg: if ($this->db->delete("login_attempts")) { goto hftmE; } goto Bh4l0; ck3Bj: goto ZaZdl; goto pWAo0; Bh4l0: $data = ["status" => false, "msg" => " gagal direset"]; goto iR6M7; GB9KJ: $data = ["status" => false, "msg" => "You must be an administrator to view this page."]; goto cgUfb; zqIwx: $this->db->where("login", $username); goto zcwhg; iR6M7: goto JV2VT; goto ioLVv; vJ4XB: JV2VT: goto ck3Bj; GX1fP: $this->output_json($data, true); goto Mx2vx; nKcDe: if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) { goto RTxd7; } goto zqIwx; pWAo0: RTxd7: goto GB9KJ; Lqia1: $data = ["status" => true, "msg" => " berhasil direset"]; goto vJ4XB; tFOY4: $username = $this->input->get("username", true); goto nKcDe; ioLVv: hftmE: goto Lqia1; cgUfb: ZaZdl: goto GX1fP; Mx2vx: } }
