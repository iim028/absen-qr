<?php
function kirimNotifikasiWA($target, $pesan) {
    // 1. TEMPEL TOKEN FONNTE DI SINI
    $token = "pz8LQXXA1ScC1YCKuBr9"; // Token Anda

    // 2. Logic Ubah 08 ke 62 (Wajib)
    $target = preg_replace('/[^0-9]/', '', $target); // Hapus spasi/strip
    if (substr($target, 0, 1) == '0') {
        $target = '62' . substr($target, 1);
    }

    // 3. Kirim ke Fonnte
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array(
        'target' => $target,
        'message' => $pesan,
      ),
      CURLOPT_HTTPHEADER => array(
        "Authorization: $token"
      ),
      // Tambahan agar tidak error SSL di Localhost
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
?>