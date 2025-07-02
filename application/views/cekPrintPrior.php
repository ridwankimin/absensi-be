<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prior Notice Validation</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Open Sans", sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            justify-content: center;
            align-items: center;
            background: linear-gradient(90deg, rgba(218, 233, 236, 1) 0%, rgba(94, 98, 112, 0.6362920168067228) 56%, rgba(212, 230, 233, 1) 100%);
            /* background: hsl(187 40% 98%); */
        }

        /* img {
            display: block;
            width: 100%;
        } */

        h2 {
            margin: 0;
            font-size: 1.4rem;
        }

        @media (min-width: 50em) {
            h2 {
                font-size: 1.8rem;
            }
        }

        .cta {
            --shadowColor: 186 60% 40%;
            display: flex;
            flex-wrap: wrap;
            background: hsl(166 45% 98%);
            /* background: hsl(187 70% 85%); */
            max-width: 50rem;
            width: 100%;
            box-shadow: 0.65rem 0.65rem 0 hsl(var(--shadowColor) / 1);
            border-radius: 0.8rem;
            overflow: hidden;
            border: 0.5rem solid;
        }

        /* .cta img {
            aspect-ratio: 3 / 2;
            object-fit: cover;
            flex: 1 1 300px;
        } */

        .cta__text-column {
            padding: min(2rem, 5vw) min(2rem, 5vw) min(2.5rem, 5vw);
            flex: 1 0 50%;
        }

        .cta__text-column>*+* {
            margin: min(1.5rem, 2.5vw) 0 0 0;
        }

        .cta a {
            display: inline-block;
            color: black;
            padding: 0.5rem 1rem;
            text-decoration: none;
            background: hsl(180 25% 85%);
            border-radius: 0.6rem;
            font-weight: 700;
            border: 0.35rem solid;
        }
    </style>
</head>

<body style="padding: 10px;">
    <article class="cta">
        <div class="cta__text-column">
            <center>
                <img src="<?= base_url('img/logobarantin.png') ?>" width="100px" alt="">
                <h2>Prior Notice Validation</h2>
            </center>
            <hr>
            <center>
                <table>
                    <tbody>
                        <tr>
                            <td width="45%">Document Number</td>
                            <td width="2%">:</td>
                            <td width="53%"><?= $data ? $data['docnbr'] : "-" ?></td>
                        </tr>
                        <tr>
                            <td width="45%">Date Issued</td>
                            <td width="2%">:</td>
                            <td width="53%"><?= $data ? $data['tgl_doc'] : "-" ?></td>
                        </tr>
                        <tr>
                            <td width="45%">Exporter</td>
                            <td width="2%">:</td>
                            <td width="53%"><?= $data ? $data['company'] : "-" ?></td>
                        </tr>
                        <tr>
                            <td width="45%">Consignee</td>
                            <td width="2%">:</td>
                            <td width="53%"><?= $data ? $data['company_imp'] : "-" ?></td>
                        </tr>
                        <tr>
                            <td width="45%">Destination</td>
                            <td width="2%">:</td>
                            <td width="53%"><?= $data ? $data['kota_tuju'] : "-"  ?></td>
                        </tr>
                        <tr>
                            <td width="45%">Status</td>
                            <td width="2%">:</td>
                            <td width="53%"><b style="color: <?= $data ? 'green' : 'red' ?>"><?= $data ? "VALID" : "INVALID" ?></b></td>
                        </tr>
                        <?php if ($data) { ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">
                                    <img src="data:image/png;base64, <?= $qrcode ?>" width="145px" style="margin: 10px;" />
                                    <br />
                                    <a href="<?= $link ?>">Download document</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </center>
        </div>
    </article>
</body>

</html>