<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prior Notice</title>
    <style>
        body {
            font-size: small;
            font-family: Arial, Helvetica, sans-serif;
        }

        .garis-hitam {
            border-color: black;
            background-color: black;
            color: black;
        }

        .b-full {
            border-right: 2px solid black;
            border-top: 2px solid black;
        }

        .b-kiba {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
        }

        .b-full {
            padding: 0;
            border: 2px solid black;
        }

        .b-full1 {
            padding: 0;
            border: 1px solid black;
        }

        #char {
            font-size: 25px;
            font-family: Verdana, Tahoma, "DejaVu Sans", sans-serif;
        }

        table,
        th,
        td {
            padding: 0;
            /* margin: 3px; */
            border: 2px solid black;
            border-collapse: collapse;
        }
    </style>
</head>

<body style="border: 2px solid black;">
    <center>
        <h3 style="margin-bottom: 4px">PRIOR NOTICE</h3>
        <p style="margin: 0;">Reference Number <?= $data['docnbr'] ?></p>
        <hr class="garis-hitam" style="width: 55%; border: none;">
    </center>
    <hr style="margin: 2px 0px;" class="garis-hitam">
    <strong style="padding: 3px;">COUNTRY OF ORIGIN : </strong><?= strtoupper($data['neg_origin']) ?>
    <hr style="margin: 2px 0px;" class="garis-hitam">
    <strong style="padding: 3px;">COUNTRY OF EXPORT : </strong> <?= strtoupper($data['place_issued']) ?>
    <table width="100%" style="border-left: 0; border-right: 0">
        <tbody>
            <tr style="border-top: 2px solid black;">
                <td width="120px" rowspan="4" style="border-left: 0; text-align: center;">Description of Exporter/ Representative*</td>
                <td width="80px" style="border-right: 0; padding: 3px;">Name</td>
                <td width="140px" style="padding: 3px;"><?= strtoupper($data['name']) ?></td>
                <td width="80px" style="padding: 3px;">Company name</td>
                <td style="border-right: 0; padding: 3px;"><?= strtoupper($data['company']) ?></td>
            </tr>
            <tr>
                <td style="border-right: 0; padding: 3px;">Address</td>
                <td colspan="3" style="border-right: 0; padding: 3px;"><?= strtoupper($data['alamat']) ?></td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 3px;">Phone/Fax. Number</td>
                <td colspan="2" style="border-right: 0; padding: 3px;"><?= strtoupper($data['telp']) ?></td>
            </tr>
            <tr>
                <td style="padding: 3px;">Email</td>
                <td colspan="3" style="border-right: 0; padding: 3px;"><?= $data['email'] ?></td>
            </tr>
            <tr style="border-top: 2px solid black;">
                <td width="120px" rowspan="4" style="border-left: 0; text-align: center;">Description of Importer/ Representative*</td>
                <td width="80px" style="padding: 3px;">Name</td>
                <td width="140px" style="padding: 3px;"><?= strtoupper($data['name_imp']) ?></td>
                <td width="80px" style="padding: 3px;">Company name</td>
                <td style="border-right: 0; padding: 3px;"><?= strtoupper($data['company_imp']) ?></td>
            </tr>
            <tr>
                <td style="padding: 3px;">Address</td>
                <td colspan="3" style="border-right: 0; padding: 3px;"><?= strtoupper($data['alamat_imp']) ?></td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 3px;">Phone/Fax. Number</td>
                <td colspan="2" style="border-right: 0; padding: 3px;"><?= strtoupper($data['telp_imp']) ?></td>
            </tr>
            <tr>
                <td style="padding: 3px;">Email</td>
                <td colspan="3" style="border-right: 0; padding: 3px;"><?= $data['email_imp'] ?></td>
            </tr>
        </tbody>
    </table>
    <center>
        <strong>DESCRIPTION OF COMMODITY/CONSIGNMENT:</strong>
    </center>
    <hr style="margin: 2px 0px;" class="garis-hitam">
    <img src="data:image/jpg;base64,<?= $data['gmo'] == "N" ? $check : $uncheck ?>" style="margin-left: 10px;" width="15px"> Non GMO <img src="data:image/jpg;base64,<?= $data['gmo'] == "Y" ? $check : $uncheck ?>" style="margin-left: 100px;" width="15px"> GMO: the CoA's reference no & date
    <table width="100%" style="border-left: 0; border-right: 0; margin-top: 10px;">
        <thead>
            <tr>
                <th style="border-left: 0; padding:3px;">No</th>
                <th style="padding: 3px;">Common Name/ Scientific Name</th>
                <th style="padding: 3px;">HS Code</th>
                <th style="padding: 3px;">Quantity/ Packaging</th>
                <th style="padding: 3px;">Certificate of Analysis/Health Certificate's Reference Number and issued date *)</th>
                <th style="border-right: 0; padding: 3px;">Testing Laboratory/ NFSCA Body*)</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($komoditas as $item) { ?>
                <tr>
                    <td style="border-left: 0; padding:3px;"><?= $no++; ?></td>
                    <td style="padding: 3px;"><?= $item['nama_ilmiah'] ?></td>
                    <td style="padding: 3px;"><?= $item['hscode'] ?></td>
                    <td style="padding: 3px;"><?= $item['jumlah'] . " " . $item['satuan_en'] . " / " . $item['jumlahKemasan'] . " " . $item['kode_kemasan'] ?></td>
                    <td style="padding: 3px;"><?= $item['coa'] . " (" . $item['datecoa'] . ")" ?></td>
                    <td style="border-right: 0; padding: 3px;"><?= $item['reglab'] ?></td>
                </tr>
            <?php } ?>

            <tr>
                <td colspan="3" style="border-left: 0; padding:3px;">EXPORT PURPOSE</td>
                <td colspan="3" style="border-right: 0;" style="padding: 4px;"><?= $data['tujuan'] ?></td>
            </tr>
            <tr>
                <td colspan="3" style="border-left: 0; padding:3px;">DEGREE OF PROCESSING</td>
                <td colspan="3" style="border-right: 0;"><img src="data:image/jpg;base64,<?= $data['processing'] == "Fresh" ? $check : $uncheck ?>" style="margin-left: 10px;" width="15px"> Fresh <img src="data:image/jpg;base64,<?= $data['processing'] == "Min" ? $check : $uncheck ?>" style="margin-left: 10px;" width="15px"> Minimal processed <img src="data:image/jpg;base64,<?= $data['processing'] == "Full" ? $check : $uncheck ?>" style="margin-left: 10px;" width="15px"> Full processed <img src="data:image/jpg;base64,<?= $data['processing'] == "Other" ? $check : $uncheck ?>" style="margin-left: 10px;" width="15px"> Other <?= $data['processingLain'] ? " (" . $data['processingLain'] . ")" : "" ?></td>
            </tr>
        </tbody>
    </table>
    <center>
        <strong style="padding: 3px;">HEALTH/SANITARY/PHYTOSANITARY CERTIFICATE*</strong>
    </center>
    <table width="100%" style="border-left: 0; border-right: 0;">
        <thead>
            <tr>
                <th style="border-left: 0; padding:3px;">No</th>
                <th style="padding: 3px;">Reference Number</th>
                <th style="padding: 3px;">Place of Issue</th>
                <th style="border-right: 0; padding: 3px;">Date of Issue</th>
            </tr>
        </thead>
        <tbody>
            <?php $num = 1; ?>
            <?php foreach ($detilcert as $cert) { ?>
                <tr>
                    <td style="border-left: 0; padding:3px;"><?= $num++ ?></td>
                    <td style="padding: 3px;"><?= $cert['nomor'] ?></td>
                    <td style="padding: 3px;"><?= $cert['issued_place'] ?></td>
                    <td style="border-right: 0; padding: 3px;"><?= $cert['issued_date'] ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <table width="100%" style="border-left: 0; border-right: 0; margin-top: 15px">
        <tbody>
            <tr>
                <td style="border-left: 0; padding:3px;" width="170px">Means of conveyance</td>
                <td width="140px" style="padding: 3px;"><?= strtoupper($data['jnsangkut']) ?></td>
                <td width="170px" style="padding: 3px;">Voyage/Flight number</td>
                <td style="border-right: 0; padding: 3px;"><?= strtoupper($data['novoyage']) ?></td>
            </tr>
            <tr>
                <td style="border-left: 0; padding:3px;" width="170px">Port of loading</td>
                <td width="140px" style="padding: 3px;"><?= strtoupper($data['port_asal']) ?></td>
                <td width="170px" style="padding: 3px;">Date of loading</td>
                <td style="border-right: 0; padding: 3px;"><?= strtoupper($data['tgl_loading']) ?></td>
            </tr>
            <tr>
                <td style="border-left: 0; padding:3px;" width="170px">Place of destination</td>
                <td width="140px" style="padding: 3px;"><?= strtoupper($data['kota_tuju']) ?></td>
                <td width="170px" style="padding: 3px;">Date of estimated arrival</td>
                <td style="border-right: 0; padding: 3px;"><?= strtoupper($data['tgl_tiba']) ?></td>
            </tr>
        </tbody>
    </table>
    <p style="margin: 1px; padding: 3px;">Additional Information: <?= $data['keterangan'] ?></p>
    <?php $kont = "";
    if (count($kontainer) > 0) {
        foreach ($kontainer as $kon) {
            $kont .= $kon['no_kont'] . "; ";
        }
    } ?>
    <p style="margin: 1px; padding: 3px;">Container's Identification Number*: <?= $kont ?></p>
    <p style="margin: 1px; padding: 3px;">Other (specify):</p>
    <hr style="margin: 2px 0px;" class="garis-hitam">
    <p style="margin: 5px 5px 5px 300px;">Place: <?= $data['place_issued'] ?></p>
    <p style="margin: 5px 5px 5px 300px;">Date: <?= substr($data['tgl_doc'], 0, 10) ?></p>
    <p style="margin: 5px 5px 5px 500px;">Applicant</p>
    <br><br><br>
    <img src="data:image/jpg;base64,<?= $qrcode ?>" style="position: fixed; bottom: 30px; left: 20px; width: 90px;" />
    <p style="margin: 5px 5px 5px 500px;">( <?= strtoupper($data['name']) ?> )</p>
</body>

</html>