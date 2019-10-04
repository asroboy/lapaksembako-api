<?php

require_once "../libs/random_compat.phar";

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
//
//require '../vendor/phpmailer/PHPMailer/src/Exception.php';
//require '../vendor/phpmailer/PHPMailer/src/PHPMailer.php';
//require '../vendor/phpmailer/PHPMailer/src/SMTP.php';

// Load Composer's autoloader
require '../vendor/autoload.php';


$container = $app->getContainer();
$container['upload_directory'] = '/home/wandevelopmentwa/public_html/lapaksembako/upload'; //<--- di server
//$container['upload_directory'] = 'C:\\xampp\\htdocs\\lapaksembako\\upload'; //<--- di lokal
// Routes

$app->get('/test', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $response->withJson(["code" => 1, "status" => "Success", "message" => "You have succesfully calling api", "data" => [["name" => "Test Data1"], ["name" => "Test Data 2"]]], 200);
//    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/user/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' user");

    // Render index view
    return $response->withJson(["code" => 1, "status" => "Success", "message" => "You have succesfully calling api", "data" => [["name" => "Test Data1"], ["name" => "Test Data 2"]]], 200);

});

$app->post("/login", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $sql = "SELECT * FROM tb_member WHERE phone=:mobile AND password=:password";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":mobile" => $request_body["mobile"],
        ":password" => $request_body["password"]];
    $stmt->execute($data);
    $result = null;
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch();
        //$this->logger->info("Result : " . $result);
        return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
    }

    return $response->withJson(["status" => "failed", "data" => $result, "message" => ""], 200);
});

$app->post("/daftar", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $sql = "INSERT INTO tb_member (full_name, phone, password, referrer_id, referral_id) VALUES (:full_name, :phone,:password, :referrer_id, :referral_id)";
    $stmt = $this->db->prepare($sql);


    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $referral_id = unique_code(8);
    $data = [
        ":full_name" => $request_body["nama"],
        ":phone" => $request_body["phone"],
        ":password" => $request_body["password"],
        ":referrer_id" => $request_body["referrer_id"],
        ":referral_id" => $referral_id
    ];
    $this->logger->info("data formattes : " . json_encode($data));
    try {
        $stmt->execute($data);
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        } else {
//            throw $e;
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        }
    }

});

$app->get("/faq", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_faq";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/category", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_product_category";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/category/[{category_id}]", function (Request $request, Response $response, $args) {
    $category_id = $args["category_id"];
    $sql = "SELECT * FROM tb_product_category where id_product_category = :category_id";
    $stmt = $this->db->prepare($sql);
    $data = [":category_id" => $category_id];
    $stmt->execute($data);
    $result = $stmt->fetch();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/referral/[{user_id}]", function (Request $request, Response $response, $args) {
    $user_id = $args["user_id"];
    $stmt1 = $this->db->prepare("SELECT * FROM tb_member where id_member =  :user_id");
    $stmt1->execute([":user_id" => $user_id]);
    $result1 = $stmt1->fetch();
    $sql = "SELECT count(*) as jumlah_downline FROM tb_member where referrer_id =  :referrer_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":referrer_id" => $result1['referral_id']]);
    $result = $stmt->fetch();
    return $response->withJson(["status" => "success", "data" => $result['jumlah_downline'], "message" => ""], 200);
});

$app->get("/flash_sale", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_promo where valid_start > CURDATE() order by id_promo desc limit 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/flash_sale_items/[{flash_id}]", function (Request $request, Response $response, $args) {
    $flash_id = $args["flash_id"];
    $sql = "SELECT * FROM tb_product_promo a left join tb_product b on a.id_product = b.id_product where id_promo = :flash_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":flash_id" => $flash_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_address/[{user_id}]", function (Request $request, Response $response, $args) {
    $user_id = $args["user_id"];
    $sql = "SELECT * FROM tb_address where id_member = :user_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":user_id" => $user_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_city/[{province_id}]", function (Request $request, Response $response, $args) {
    $province_id = $args["province_id"];
    $sql = "SELECT * FROM tb_city where province_id=:province_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":province_id" => $province_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_province", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_province";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_items_by_category", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_product a inner join tb_product_category b group by a.id_product_category";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_items/[{category_id}]", function (Request $request, Response $response, $args) {
    $category_id = $args["category_id"];
    $sql = "SELECT * FROM tb_product a LEFT JOIN tb_photo c ON a.id_product = c.id_product  WHERE id_product_category=:category_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":category_id" => $category_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_items_20", function (Request $request, Response $response, $args) {
    $category_id = $args["category_id"];
    $sql = "SELECT * FROM tb_product limit 20";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/list_items_promo/[{promo_id}]", function (Request $request, Response $response, $args) {
    $promo_id = $args["promo_id"];
    $sql = "SELECT * FROM tb_product_promo a 
            inner join tb_product b 
            LEFT JOIN tb_photo c ON b.id_product = c.id_product 
            WHERE a.id_promo=:id_promo";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id_promo" => $promo_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->get("/akun_bank/[{user_id}]", function (Request $request, Response $response, $args) {
    $user_id = $args["user_id"];
    $sql = "SELECT * FROM tb_bank_account where id_member = :user_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":user_id" => $user_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->post("/simpan_akun_bank", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "INSERT INTO  tb_bank_account (bank_name, bank_account, account_number, id_member) VALUES (:bank_name, :bank_account, :account_number, :id_member)";
    $stmt = $this->db->prepare($sql);

    $this->logger->info("data recieved : " . implode(", ", $request_body));

    $data = [
        ":bank_name" => $request_body["bank_name"],
        ":bank_account" => $request_body["bank_account"],
        ":account_number" => $request_body["account_number"],
        ":id_member" => $request_body["id_member"]
    ];
    $this->logger->info("data formattes : " . json_encode($data));
    try {
        $stmt->execute($data);
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        } else {
//            throw $e;
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        }
    }

});

$app->get("/default_address/[{user_id}]", function (Request $request, Response $response, $args) {
    $user_id = $args["user_id"];
    $sql = "SELECT * FROM tb_address where id_member = :user_id and is_default = true";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":user_id" => $user_id]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->post("/update_profile", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $sql = "UPDATE tb_member SET full_name = :full_name, phone = :phone, email = :email where id_member = :user_id";
    $stmt = $this->db->prepare($sql);


    $this->logger->info("data recieved : " . implode(", ", $request_body));

    $data = [
        ":full_name" => $request_body["nama"],
        ":phone" => $request_body["phone"],
        ":email" => $request_body["email"],
        ":user_id" => $request_body["user_id"]
    ];
    $this->logger->info("data formattes : " . json_encode($data));
    try {
        $stmt->execute($data);
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        } else {
//            throw $e;
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        }
    }

});

$app->post("/simpan_alamat", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "INSERT INTO  tb_address (id_member, id_province, id_city, address, postal_code, phone, name_acc, created_by, is_default) VALUES (:id_member, :id_province, :id_city, :address, :postal_code, :phone, :name_acc, :created_by, :is_default)";
    $stmt = $this->db->prepare($sql);

    $this->logger->info("data recieved : " . implode(", ", $request_body));

    $data = [
        ":id_member" => $request_body["user_id"],
        ":id_province" => $request_body["id_province"],
        ":id_city" => $request_body["id_city"],
        ":address" => $request_body["address"],
        ":postal_code" => $request_body["postal_code"],
        ":phone" => "-",
        ":name_acc" => "-",
        ":created_by" => $request_body["user_id"],
        ":is_default" => $request_body["is_default"]
    ];
    $this->logger->info("data formattes : " . json_encode($data));
    try {
        $stmt->execute($data);
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        } else {
//            throw $e;
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
        }
    }

});

$app->post('/image_profile', function (Request $request, Response $response) {
    $this->logger->info("image_profile called");
    $request_body = $request->getParsedBody();
    $directory = $this->get('upload_directory') . "/member";
    $sql = "SELECT id_member, profile_pic from tb_member where id_member = :id_member";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":id_member" => $request_body["user_id"]
    ];

    $stmt->execute($data);
    $result = $stmt->fetch();
    $pic_file_name = $result['profile_pic'];
    $this->logger->info("profile pic : " . $pic_file_name);
    $uploadedFiles = $request->getUploadedFiles();
    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['foto'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        if ($pic_file_name === "" || $pic_file_name === null) {
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $basename = bin2hex(random_bytes(5)); // see http://php.net/manual/en/function.random-bytes.php
            $pic_file_name = sprintf('%s.%0.8s', $basename, $extension);
        }

        $filename = moveUploadedFile($directory, $uploadedFile, $pic_file_name);

        $sql2 = "UPDATE tb_member set profile_pic=:profile_pic  where id_member = :id_member";
        $stmt2 = $this->db->prepare($sql2);
        $data2 = [
            ":profile_pic" => $filename,
            ":id_member" => $request_body["user_id"]
        ];
        try {
            $stmt2->execute($data2);
            return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
        } catch (PDOException $e) {
            $this->logger->info("Data gagal disimpan " . $e->getMessage());
            if ($e->getCode() == 1062) {
                return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
            } else {
                return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
            }
        }
    } else {
        $this->logger->info("Data gagal disimpan " . $uploadedFile->getError());
        return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $uploadedFile->getError() . UPLOAD_ERR_OK], 200);
    }

});

function moveUploadedFile($directory, UploadedFile $uploadedFile, $filename)
{
    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
    return $filename;
}

$app->get("/periksa_keranjang", function (Request $request, Response $response, $args) {
    $user_id = $request->getQueryParam("user_id");
    $wholesale_status = $request->getQueryParam("wholesale_status");
    $sql = "SELECT * FROM tb_cart where id_member = :id_member and wholesale_status = :wholesale_status";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id_member" => $user_id, ":wholesale_status" => $wholesale_status]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->post("/tambah_keranjang", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "INSERT INTO tb_cart (id_member, wholesale_status) VALUES (:id_member, :wholesale_status)";
    $stmt = $this->db->prepare($sql);

    $this->logger->info("data recieved : " . implode(", ", $request_body));

    $data = [
        ":id_member" => $request_body["user_id"],
        ":wholesale_status" => $request_body["wholesale_status"]
    ];
    $this->logger->info("data formattes : " . json_encode($data));
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
//            throw $e;
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }

});

$app->get("/ambil_item_keranjang", function (Request $request, Response $response, $args) {
    $user_id = $request->getQueryParam("user_id");
    $wholesale_status = $request->getQueryParam("wholesale_status");
    $sql = "SELECT * FROM tb_cart_detail a 
            left join tb_cart b on a.id_cart = b.id_cart 
            left join tb_product c on c.id_product = a.id_product 
            LEFT JOIN tb_photo d ON a.id_product = d.id_product 
            where b.id_member = :id_member and b.wholesale_status = :wholesale_status";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":id_member" => $user_id, ":wholesale_status" => $wholesale_status]);
    $result = $stmt->fetchAll();
    return $response->withJson(["status" => "success", "data" => $result, "message" => ""], 200);
});

$app->post("/tambah_item_ke_cart", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql1 = "SELECT * FROM tb_cart_detail where id_cart=:id_cart and id_product=:id_product";
    $stmt1 = $this->db->prepare($sql1);
    $data1 = [
        ":id_cart" => $request_body["id_cart"],
        ":id_product" => $request_body["id_product"]
    ];
    $stmt1->execute($data1);
    $result1 = $stmt1->fetch();

    $id1 = $result1['id_cart_detail'];

    $this->logger->info("ID cart : " . json_encode($id1));

    if ($id1 > 0) {
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $id1], 200);
    } else {
        $sql = "INSERT INTO tb_cart_detail (id_cart, id_product, product_price, quantity, total_price) VALUES (:id_cart, :id_product, :product_price, :quantity, :total_price)";
        $stmt = $this->db->prepare($sql);
        $this->logger->info("data recieved : " . implode(", ", $request_body));

        $data = [
            ":id_cart" => $request_body["id_cart"],
            ":id_product" => $request_body["id_product"],
            ":product_price" => $request_body["product_price"],
            ":quantity" => $request_body["quantity"],
            ":total_price" => $request_body["total_price"]
        ];
        $this->logger->info("data formattes : " . json_encode($data));
        try {
            $stmt->execute($data);
            $insertedId = $this->db->lastInsertId();;
            return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
        } catch (PDOException $e) {
            if ($e->getCode() == 1062) {
                // Take some action if there is a key constraint violation, i.e. duplicate name
                return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
            } else {
                return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
            }
        }
    }
});

$app->post("/hapus_item_darie_cart", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql1 = "DELETE FROM tb_cart_detail where id_cart=:id_cart and id_product=:id_product";
    $this->logger->info("Q : " . $sql1);

    $stmt1 = $this->db->prepare($sql1);
    $data1 = [
        ":id_cart" => $request_body["id_cart"],
        ":id_product" => $request_body["id_product"]
    ];

    $this->logger->info("D : " . json_encode($data1));
    $stmt1->execute($data1);
    return $response->withJson(["status" => "success", "message" => "Data berhasil dihapus"], 200);
});

$app->post("/update_qty", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $qty = $request_body['quantity'];

    $sql = "SELECT * FROM tb_cart_detail where id_cart=:id_cart and id_product=:id_product";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":id_cart" => $request_body["id_cart"],
        ":id_product" => $request_body["id_product"]
    ];

    $stmt->execute($data);
    $result1 = $stmt->fetchAll();

    $price = $result1['product_price'];

    $total_price = $qty * $price;

    $sql1 = "UPDATE tb_cart_detail set quantity =:quantity, total_price=:total_price where id_cart=:id_cart and id_product=:id_product";
    $this->logger->info("Q : " . $sql1);

    $data1 = [
        ":id_cart" => $request_body["id_cart"],
        ":id_product" => $request_body["id_product"],
        ":quantity" => $qty,
        ":total_price" => $total_price
    ];

    $stmt1 = $this->db->prepare($sql1);
    $this->logger->info("D : " . json_encode($data1));
    $stmt1->execute($data1);
    return $response->withJson(["status" => "success", "message" => "Data berhasil dihapus"], 200);
});

$app->post("/reset_password/[{email}]", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "SELECT * FROM tb_member WHERE email = :email";
    $stmt = $this->db->prepare($sql);

    $this->logger->info("data recieved : " . implode(", ", $request_body));

    $data = [
        ":email" => $request_body["email"]
    ];
    $stmt->execute($data);
    $result = $stmt->fetch();

    $id = $result['id'];
    if ($id > 0) {
        return $response->withJson(["status" => "success", "message" => "Pesan dikirimkan ke email Anda, ikuti langkah untuk melakukan reset password"], 200);
    } else {
        return $response->withJson(["status" => "failed", "message" => "Email tidak ditemukan, silahkan coba lagi dengan memasukkan email Anda dengan benar"], 200);
    }

    $this->logger->info("data formattes : " . json_encode($data));

});

$app->post("/suka", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "INSERT INTO tb_wishlist (id_member, id_product) values (:id_member, :id_product) ON DUPLICATE KEY UPDATE id_member=:id_member, id_product=:id_product";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $data = [
        ":id_member" => $request_body["id_member"],
        ":id_product" => $request_body["id_product"]
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});

$app->post("/tidak_suka", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql1 = "DELETE FROM tb_wishlist where id_member=:id_member and id_product=:id_product";
    $this->logger->info("Q : " . $sql1);

    $stmt1 = $this->db->prepare($sql1);
    $data1 = [
        ":id_member" => $request_body["id_member"],
        ":id_product" => $request_body["id_product"]
    ];
    $this->logger->info("D : " . json_encode($data1));
    $stmt1->execute($data1);
    return $response->withJson(["status" => "success", "message" => "Data berhasil dihapus"], 200);
});

$app->get("/item_disukai/[{user_id}]", function (Request $request, Response $response, $args) {
    $id_member = $args['user_id'];
    $sql = "SELECT * FROM tb_wishlist a INNER JOIN tb_product b ON a.id_product = b.id_product where a.id_member=:id_member";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":id_member" => $id_member
    ];
    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "\"Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/is_item_disukai", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $id_product = $request->getQueryParam("id_product");
    $sql = "SELECT * FROM tb_wishlist where id_member=:id_member and id_product=:id_product";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":id_member" => $id_member,
        ":id_product" => $id_product
    ];
    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        $is_disukai = sizeof($result) > 0;
        return $response->withJson(["status" => "success", "message" => "Berhasil", "is_like" => $is_disukai], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "is_like" => false], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "\"Gagal mengambil data " . $e->getMessage(), "is_like" => false], 200);
        }
    }
});

$app->get("/get_referral", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_member where id_member=:id_member";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);

    $data = [
        ":id_member" => $id_member
    ];
    $this->logger->info("D : " . json_encode($data));

    try {
        $stmt->execute($data);
        $result = $stmt->fetch();
        $referral_id = $result['referral_id'];
        $this->logger->info("R : " . $referral_id);


        $sql1 = "SELECT * FROM tb_member where referrer_id=:referral_id";
        $stmt1 = $this->db->prepare($sql1);
        $data1 = [
            ":referral_id" => $referral_id
        ];
        $stmt1->execute($data1);
        $result1 = $stmt1->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result1, "referral_id" => $referral_id], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_balance", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_balance where id_member=:id_member order by id_balance desc limit 1";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    $data = [
        ":id_member" => $id_member
    ];
    $this->logger->info("D : " . json_encode($data));

    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_history_balance", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_balance where id_member=:id_member order by id_balance desc";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    $data = [
        ":id_member" => $id_member
    ];
    $this->logger->info("D : " . json_encode($data));

    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_poin", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_poin_balance where id_member=:id_member order by id_poin_balance desc limit 1";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    $data = [
        ":id_member" => $id_member
    ];
    $this->logger->info("D : " . json_encode($data));

    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_history_poin", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_poin_balance where id_member=:id_member order by id_poin_balance desc";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    $data = [
        ":id_member" => $id_member
    ];
    $this->logger->info("D : " . json_encode($data));

    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->post("/withdraw", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $sql = "INSERT INTO tb_withdrawal (id_member, type, bank_name, bank_account, account_number, nominal, status) values (:id_member, :m_type, :bank_name, :bank_account, :account_number, :nominal, :status)";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $data = [
        ":id_member" => $request_body["id_member"],
        ":m_type" => $request_body["m_type"],
        ":bank_name" => $request_body["bank_name"],
        ":bank_account" => $request_body["bank_account"],
        ":account_number" => $request_body["account_number"],
        ":nominal" => $request_body["nominal"],
        ":status" => $request_body["status"]
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});

$app->get("/get_delivery_price", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_delivery_price limit 1";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    try {
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->post("/create_transaction", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $this->logger->info("Data recieved : " . json_encode($request_body));

    $sql = "INSERT INTO tb_transaction(transaction_code, id_member, id_province, id_city, id_district, id_village, address, postal_code, latitude, longitude, note, delivery_price, total_nominal, payment_status, payment_date, status) 
          VALUES (:transaction_code, :id_member, :id_province, :id_city, :id_district, :id_village, :address, :postal_code, :latitude, :longitude, :note, :delivery_price, :total_nominal, :payment_status, :payment_date, :status)";

    $stmt = $this->db->prepare($sql);
    $data = [
        ":transaction_code" => $request_body["transaction_code"],
        ":id_member" => $request_body["id_member"],
        ":id_province" => $request_body["id_province"],
        ":id_city" => $request_body["id_city"],
        ":id_district" => $request_body["id_district"],
        ":id_village" => $request_body["id_village"],
        ":address" => $request_body["address"],
        ":postal_code" => $request_body["postal_code"],
        ":latitude" => $request_body["latitude"],
        ":longitude" => $request_body["longitude"],
        ":note" => $request_body["note"],
        ":delivery_price" => $request_body["delivery_price"],
        ":payment_status" => $request_body["payment_status"],
        ":total_nominal" => $request_body["total_nominal"],
        ":payment_date" => $request_body["payment_date"],
        ":status" => $request_body["status"]
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});

$app->post("/create_transaction_detail", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $data_array = json_encode($request_body, true);
    $this->logger->info("data rcv " . $data_array);
    $sql = "INSERT INTO tb_transaction_detail (transaction_code, id_product, product_price, promo_discount, voucher_code, voucher_discount, quantity, total_price) VALUES
 (:transaction_code, :id_product, :product_price, :promo_discount, :voucher_code, :voucher_discount, :quantity, :total_price)";

    $stmt = $this->db->prepare($sql);

    foreach ($request_body as $key => $value) {
        $data = [
            ":transaction_code" => $value["transaction_code"],
            ":id_product" => $value["id_product"],
            ":product_price" => $value["product_price"],
            ":promo_discount" => $value["promo_discount"],
            ":voucher_code" => $value["voucher_code"],
            ":voucher_discount" => $value["voucher_discount"],
            ":quantity" => $value["quantity"],
            ":total_price" => $value["total_price"]
        ];
        $this->logger->info("data  " . json_encode($data));
        try {
            $stmt->execute($data);
        } catch (PDOException $e) {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }

    try {
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => 0], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});

$app->get("/get_pending_transaction", function (Request $request, Response $response, $args) {
    $id_member = $request->getQueryParam('user_id');
    $sql = "SELECT * FROM tb_transaction where id_member = :id_member and status='Pending'";
    $stmt = $this->db->prepare($sql);
    $this->logger->info("Q : " . $sql);
    $data = [
        ":id_member" => $id_member,
    ];
    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_image_items", function (Request $request, Response $response, $args) {
    $id = $request->getQueryParam('id_product');
    $sql = "SELECT * FROM tb_photo where id_product = :product";
    $stmt = $this->db->prepare($sql);
    $data = [
        ":product" => $id,
    ];
    try {
        $stmt->execute($data);
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_slider", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_slider where type = 'Android' order by sequence_number asc";
    $stmt = $this->db->prepare($sql);
    try {
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_syarat_dan_ketentuan", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_page where slug = 'Syarat-dan-Ketentuan'";
    $stmt = $this->db->prepare($sql);
    try {
        $stmt->execute();
        $result = $stmt->fetch();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->get("/get_tos", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_page where slug = 'Kebijakan-Privasi'";
    $stmt = $this->db->prepare($sql);
    try {
        $stmt->execute();
        $result = $stmt->fetch();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});

$app->post("/bukti_transfer", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();
    $directory = $this->get('upload_directory') . "/transfer";

    $uploadedFiles = $request->getUploadedFiles();
    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['image'];
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(5)); // see http://php.net/manual/en/function.random-bytes.php
        $pic_file_name = sprintf('%s.%0.8s', $basename, $extension);
    }
    $filename = moveUploadedFile($directory, $uploadedFile, $pic_file_name);
    $sql = "INSERT INTO tb_transfer_proof (transaction_code,id_member,bank_name,bank_account,account_number,nominal,attachment,is_verified) values (:transaction_code,:id_member,:bank_name,:bank_account,:account_number,:nominal,:attachment,:is_verified)";

    $stmt = $this->db->prepare($sql);
//    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $this->logger->info("data recieved : " . json_encode($request_body));
    $data = [
        ":transaction_code" => $request_body["transaction_code"],
        ":id_member" => $request_body["id_member"],
        ":bank_name" => $request_body["bank_name"],
        ":bank_account" => $request_body["bank_account"],
        ":account_number" => $request_body["account_number"],
        ":nominal" => $request_body["nominal"],
        ":attachment" => $filename,
        ":is_verified" => 0,
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});

$app->post("/komisi", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $sql = "INSERT INTO tb_balance (id_member, prev_balance, next_balance, nominal, status) values (:id_member,:prev_balance,:next_balance	,:nominal,:status)";

    $stmt = $this->db->prepare($sql);
//    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $this->logger->info("data recieved : " . json_encode($request_body));
    $data = [
        ":id_member" => $request_body["id_member"],
        ":prev_balance" => $request_body["prev_balance"],
        ":next_balance" => $request_body["next_balance"],
        ":nominal" => $request_body["nominal"],
        ":status" => $request_body["status"]
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});


$app->post("/poin", function (Request $request, Response $response, $args) {
    $request_body = $request->getParsedBody();

    $sql = "INSERT INTO tb_poin_balance (id_member, prev_balance, next_balance, nominal, status) values (:id_member,:prev_balance,:next_balance	,:nominal,:status)";

    $stmt = $this->db->prepare($sql);
    $this->logger->info("data recieved : " . json_encode($request_body));
//    $this->logger->info("data recieved : " . implode(", ", $request_body));
    $data = [
        ":id_member" => $request_body["id_member"],
        ":prev_balance" => $request_body["prev_balance"],
        ":next_balance" => $request_body["next_balance"],
        ":nominal" => $request_body["nominal"],
        ":status" => $request_body["status"]
    ];
    try {
        $stmt->execute($data);
        $insertedId = $this->db->lastInsertId();;
        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan", "id" => $insertedId], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage(), "id" => 0], 200);
        }
    }
});


$app->get("/get_balance_setting", function (Request $request, Response $response, $args) {
    $sql = "SELECT * FROM tb_balance_setting limit 1";
    $stmt = $this->db->prepare($sql);
    try {
        $stmt->execute();
        $result = $stmt->fetch();
        return $response->withJson(["status" => "success", "message" => "Berhasil", "data" => $result], 200);
    } catch (PDOException $e) {
        if ($e->getCode() == 1062) {
            // Take some action if there is a key constraint violation, i.e. duplicate name
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data" . $e->getMessage(), "data" => null], 200);
        } else {
            return $response->withJson(["status" => "failed", "message" => "Gagal mengambil data " . $e->getMessage(), "data" => null], 200);
        }
    }
});


function unique_code($limit)
{
    return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
}


//function sendEmail($emailAddress, UploadedFile $uploadedFile, $filename)
//{
//    // Instantiation and passing `true` enables exceptions
//    $mail = new PHPMailer(true);
//
//    try {
//        //Server settings
//        $mail->SMTPDebug = 2;                                       // Enable verbose debug output
//        $mail->isSMTP();                                            // Set mailer to use SMTP
//        $mail->Host = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup SMTP servers
//        $mail->SMTPAuth = true;                                   // Enable SMTP authentication
//        $mail->Username = 'user@example.com';                     // SMTP username
//        $mail->Password = 'secret';                               // SMTP password
//        $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
//        $mail->Port = 587;                                    // TCP port to connect to
//
//        //Recipients
//        $mail->setFrom('from@example.com');
//        $mail->addAddress($emailAddress);     // Add a recipient
//        $mail->addReplyTo('info@example.com', 'Information');
//        $mail->addCC('cc@example.com');
//        $mail->addBCC('bcc@example.com');
//
//        // Attachments
//        $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//        $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
//
//        // Content
//        $mail->isHTML(true);                                  // Set email format to HTML
//        $mail->Subject = 'Here is the subject';
//        $mail->Body = 'This is the HTML message body <b>in bold!</b>';
//        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
//
//        $mail->send();
//        echo 'Message has been sent';
//    } catch (Exception $e) {
//        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
//    }
//}


//
//$app->post("/update_alamat", function (Request $request, Response $response, $args) {
//    $request_body = $request->getParsedBody();
//    $sql = "UPDATE tb_member SET id_member', 'id_province', 'id_city', 'address', 'postal_code', 'phone', 'name_acc', 'created_date',  'created_by', 'is_default') VALUES (:id_member, :id_province, :id_city, :address, :postal_code, :phone, :name_acc, :created_date, :created_by, :is_default)";
//    $stmt = $this->db->prepare($sql);
//
//
//    $this->logger->info("data recieved : " . implode(", ", $request_body));
//
//    $data = [
//        ":id_member" => $request_body["id_member"],
//        ":id_province" => $request_body["id_province"],
//        ":id_city" => $request_body["id_city"],
//        ":address" => $request_body["address"],
//        ":postal_code" => $request_body["postal_code"],
//        ":phone" => "-",
//        ":name_acc" => "-",
//        ":created_date" => time(),
//        ":created_by" => $request_body["id_member"],
//        ":is_default" => $request_body["is_default"]
//    ];
//    $this->logger->info("data formattes : " . json_encode($data));
//    try {
//        $stmt->execute($data);
//        return $response->withJson(["status" => "success", "message" => "Data berhasil disimpan"], 200);
//    } catch (PDOException $e) {
//        if ($e->getCode() == 1062) {
//            // Take some action if there is a key constraint violation, i.e. duplicate name
//            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
//        } else {
////            throw $e;
//            return $response->withJson(["status" => "failed", "message" => "Data gagal disimpan " . $e->getMessage()], 200);
//        }
//    }
//
//});