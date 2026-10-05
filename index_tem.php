<?php 
if (isset($_SESSION["position"])) {
    $position = $_SESSION["position"];
    if ($position > 4) {
        // echo 555;
        Header("Location: architect/index");
    } else {
        Header("Location: sale/index");
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="./css/index.css"> -->
    <title>Login</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>


    <body>


        <div class="login-page bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <h1 class="mb-3">Login</h1>
                        <?php
                        if (isset($_SESSION["error"])) {
                            echo "<div class='alert alert-danger w-100 text-center h3'>" . $_SESSION["error"] . "</div>";
                            unset($_SESSION["error"]);
                        }
                        if (isset($_SESSION["success"])) {
                            echo "<div class='alert alert-success w-100 text-center h3'>" . $_SESSION["success"] . "</div>";
                            unset($_SESSION["success"]);
                        }
                        ?>

                        <div class="bg-white shadow rounded">
                            <div class="row">
                                <div class="col-md-7 pe-0">
                                    <div class="form-left h-100 py-5 px-5">
                                        <form action="login.php" class="row g-4" method="post">
                                            <div class="col-12">
                                                <label>Username<span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="bi bi-person-fill"></i></div>
                                                    <input type="text" value="<?=$username?>" name="username" class="form-control" placeholder="Enter Username">
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label>Password<span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="bi bi-lock-fill"></i></div>
                                                    <input type="password" value="<?=$pass?>"name="password" class="form-control" placeholder="Enter Password">
                                                </div>
                                            </div>

                                            <!-- <div class="col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="inlineFormCheck">
                                                    <label class="form-check-label" for="inlineFormCheck">Remember me</label>
                                                </div>
                                            </div> -->

                                            <div class="col-12">
                                                <button type="submit" class="btn btn-outline-primary px-4 float-end mt-4">login</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-md-5 ps-0 d-none d-md-block">
                                    <div class="form-right h-100 bg-primary text-white text-center pt-5"><br><br>
                                        <i class="fa-solid fa-l fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-a fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-n fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-d fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-y fa-bounce" style="font-size: 5rem;"></i><br>
                                        <i class="fa-solid fa-h fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-o fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-m fa-bounce" style="font-size: 5rem;"></i>
                                        <i class="fa-solid fa-e fa-bounce" style="font-size: 5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS -->

    </body>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>