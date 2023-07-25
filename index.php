<?php
// Cegah cache - https://stackoverflow.com/questions/49547/how-do-we-control-web-page-caching-across-all-browsers
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

require_once __DIR__ . '/config/database.php';
// require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/includes/common_functions.php';
require_once __DIR__ . '/classes/LogLoader.php';
?>

<!DOCTYPE html>
<html lang="id">
    <head>
        <title>Data Harga GPU Dari Beberapa Toko di Tokopedia</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/x-icon" href="favicon.png">
        
        <link href="css/normalize.min.css" rel="stylesheet">
        <link href="css/style.min.css" rel="stylesheet">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    
        <style>
            #gpu_log_history {
                background-color: #EEE;
                width: 100%;
                margin: 20px auto;
            }
            @media(max-width:1024px){
                #gpu_log_history {
                    width: auto;
                }
            }
            #gpu_log_history .gpu_log_history-header {
                background-color: #333;
                -webkit-user-select: none;
                padding: 10px;

                text-shadow: 1px 1px 1px #fff;
                font-weight: 400;
                font-size: .85em;
                letter-spacing: .1em;
                text-transform: uppercase;
                text-align: center;
                color: #ffffff;
            }
            #gpu_log_history .gpu_log_history-body {
                background-color: #FFF;
                border: solid 1px #DDD;
                overflow-x: hidden;
                overflow-y: scroll;
                height: calc(100% - 45px);
                max-height: 400px;
            }

            #gpu_log_history .gpu_log_history-body .log_item {
                font-family: consolas;
                font-size: 14px;
                letter-spacing: .5px;
                word-spacing: 3px;
                color: rgb(106, 112, 115);
                line-height: 1.2;
                word-wrap: break-word;
                padding: 2px 0;
                /* border-bottom: 1px solid rgb(230, 230, 230);

                margin-top: 5px; */
            }
            #gpu_log_history .gpu_log_history-body .post:nth-child(2n){
                background:#ccc
            }
            #gpu_log_history button.gpu-log-view-more {
                background: none;
                border: 1px solid #ffffff;
                text-decoration: none;
                color: #fff;
                cursor: pointer;
                padding: 5px;
            }
            #gpu_log_history button.gpu-log-view-more:hover {
                color: #ccc;
            }
            
            .loading-image {
                margin: auto;
                animation: rotation 1s infinite linear;
                display: none;
                max-width: 0.9em;
                max-height: 0.9em;
            }
            @keyframes rotation {
                100% {transform: rotate(360deg);}
            }

            #nav_menu {
            display: flex;
            flex-direction: column;
            }
            #nav_menu input
            {
            display: flex;
            width: 40px;
            height: 32px;
            position: absolute;
            cursor: pointer;
            opacity: 0;
            z-index: 2;
            }

            #nav_menu span
            {
            display: flex;
            width: 29px;
            height: 2px;
            margin-bottom: 5px;
            position: relative;
            background: #000000;
            border-radius: 3px;
            z-index: 1;
            transform-origin: 5px 0px;
            transition: transform 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
                        background 0.5s cubic-bezier(0.77,0.2,0.05,1.0),
                        opacity 0.55s ease;
            }
            #nav_menu span:first-child
            {
            transform-origin: 0% 0%;
            }

            #nav_menu span:nth-last-child(2)
            {
            transform-origin: 0% 100%;
            }

            #nav_menu input:checked ~ span:nth-last-child(3)
            {
            opacity: 1;
            transform: rotate(45deg) translate(-2px, -1px);
            background: #36383F;
            }
            #nav_menu input:checked ~ span
            {
            transform: rotate(-45deg) translate(0, -3px);
            }
            #nav_menu input:checked ~ span:nth-last-child(2)
            {
            opacity: 0;
            transform: rotate(0deg) scale(0.2, 0.2);
            }

            
        </style>
    </head>

    <body>
        <section id="gpu_log_history" class="container">
            <div class="add-new-data">
                <h4>Tambah data baru</h4>
                <form action="new_url.php" method="POST">
                    <div class="input-group mb-3">
                        <input type="text" id="add_url" name="add_url" class="form-control" placeholder="Masukkan URL" aria-label="Masukkan URL" aria-describedby="button-tambah-url" required>
                        <button class="btn btn-outline-success" type="button" id="button-tambah-url">Tambah</button>
                    </div>
                </form>
            </div>
            <div class="gpu_log_history-header">
                Daftar perubahan harga 
                <small>(Atas terbaru)</small>
                <button type="button" class="gpu-log-view-more js-gpu-log-view-more">
                    <span>Lebih banyak..</span>
                    <img class="loading-image" src="./img/loader.svg" alt="">
                </button>
            </div>
            <div class="gpu_log_history-body">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th scope="col" class="col-2">#</th>
                            <th scope="col" class="col-6">Name</th>
                            <th scope="col" class="col-2">Price</th>
                            <th scope="col" class="col-2">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $logLoader = new LogLoader($conn);
                            $result = $logLoader->loadLogs();
                            $html = $result['html'];
                            echo $html;
                            $totalRecord = $result['totalRecord'];
                        ?>
                    </tbody>
                </table>
                <input type="hidden" id="row" value="0" autocomplete=off>
                <input type="hidden" id="all" value="<?php echo $totalRecord; ?>">
            </div>
        </section>

        <footer class="container">
            <div class="col-12">
                Oleh <a href="https://miretazam.com">Miretazam</a>
            </div>
        </footer>
        
        <script src="js/jquery-3.2.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
        
        <script src="js/ajax-view-more.min.js"></script>
    </body>
</html>

<?php $conn = null;?>