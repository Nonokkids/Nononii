<?php
$pengirim = $_GET['pengirim'];
$nama = $_GET['nama'];
$jumlah = $_GET['jumlah'];
$id_penerima = $_GET['id_penerima'];
$nama_penerima = $_GET['nama_penerima'];

$token="TOKEN BO MU";
$header=  array("Authorization: Bearer ".$token );


// Deduct balance from the sender
$post_body_kurang = array(
    "id_user" => $pengirim,
    "tipe" => "kurang",
    "jumlah" => $jumlah
  );
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://bukaolshop.net/api/v1/member/saldo");
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_body_kurang));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  $hasil_kurang = curl_exec($ch);
  curl_close($ch);
  
  // Wait for 5 seconds before adding balance to the receiver
  sleep(6);
  
  // Add balance to the receiver
  $post_body_tambah = array(
    "id_user" => $id_penerima,
    "tipe" => "tambah",
    "jumlah" => $jumlah
  );
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://bukaolshop.net/api/v1/member/saldo");
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_body_tambah));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  $hasil_tambah = curl_exec($ch);
  curl_close($ch);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Saldo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .success-message {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    <style>
    .saldo-amount {
        font-size: 48px;
        font-weight: bold;
        animation: blink-animation 1s infinite;
    }

    @keyframes blink-animation {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }
</style>

</head>
<div class="card-body">
    <h1 class="success-message">Transfer berhasil dilakukan!</h1>
    <p class="text-center">Anda telah mentransfer saldo sebesar <span id="jumlah" class="saldo-amount"><?php echo $jumlah; ?></span></p>
    <ul class="list-group">
        <li class="list-group-item">Pengirim: <?php echo $nama; ?></li>
        <li class="list-group-item">ID Pengirim: <?php echo $pengirim; ?></li>
    </ul>
    <ul class="list-group">
        <li class="list-group-item">Penerima: <?php echo $nama_penerima; ?></li>
        <li class="list-group-item">ID Penerima: <?php echo $id_penerima; ?></li>
    </ul>
</div>

</html>
<script src="https://www.gstatic.com/firebasejs/7.0.0/firebase.js"></script>
<script>
    var jumlah = '<?php echo $jumlah; ?>';
    document.getElementById("jumlah").innerText = formatRupiah(jumlah);
      // Fungsi untuk memformat angka menjadi format mata uang rupiah
      function formatRupiah(angka) {
      var number_string = angka.toString().replace(/[^,\d]/g, '');
      var split = number_string.split(',');
      var sisa = split[0].length % 3;
      var rupiah = split[0].substr(0, sisa);
      var ribuan = split[0].substr(sisa).match(/\d{3}/gi);

      // Tambahkan titik jika ada ribuan
      if (ribuan) {
        var separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
      }

      // Tambahkan koma dan 2 digit desimal jika ada
      rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
  
      return 'Rp ' + rupiah;
    }

</script>
<script>
    // Inisialisasi Firebase
    var firebaseConfig = {
                //FIREBASE CONFIG MU
            };
		firebase.initializeApp(firebaseConfig);

    // Membuat reference database Firebase
    var db = firebase.database();

    // Simpan data ke Firebase
    var jumlah = '<?php echo $jumlah; ?>';
    var pengirim = '<?php echo $pengirim; ?>';
    var penerima = '<?php echo $pengirim; ?>';
    var namaPenerima = '<?php echo $nama_penerima; ?>';
    var namaPengirim = '<?php echo $nama; ?>';
    var ketKirim = "kirim ke " + namaPenerima;
    var ketTerima = "terima dari " + namaPengirim;
    var kirimRef = db.ref(pengirim + "/transfer/kirim");
    var terimaRef = db.ref(penerima + "/transfer/terima");

    // Simpan data kirim ke Firebase
    kirimRef.push().set({
        jumlah: jumlah,
        keterangan: ketKirim,
        tanggal: getFormattedDate()
    });

    // Simpan data terima ke Firebase
    terimaRef.push().set({
        jumlah: jumlah,
        keterangan: ketTerima,
        tanggal: getFormattedDate()
    });

    // Batasi jumlah data menjadi 20
    limitData(kirimRef);
    limitData(terimaRef);

    // Fungsi untuk membatasi jumlah data
    function limitData(ref) {
        ref.once("value", function(snapshot) {
            if (snapshot.numChildren() >= 20) {
                var oldestChild = snapshot.val();
                var oldestKey = Object.keys(oldestChild)[0];
                ref.child(oldestKey).remove();
            }
        });
    }

    // Fungsi untuk mendapatkan tanggal dalam format tertentu
    function getFormattedDate() {
        var date = new Date();
        var year = date.getFullYear();
        var month = (date.getMonth() + 1).toString().padStart(2, "0");
        var day = date.getDate().toString().padStart(2, "0");
        var hour = date.getHours().toString().padStart(2, "0");
        var minute = date.getMinutes().toString().padStart(2, "0");
        var second = date.getSeconds().toString().padStart(2, "0");
        return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
    }
</script>

