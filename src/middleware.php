<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);



// middleware untuk validasi api key
$app->add(function ($request, $response, $next) {

    $key = $request->getQueryParam("token");

    if(!isset($key)){
        return $response->withJson(["status" => "API Key required"], 401);
    }

    $sql = "SELECT * FROM api_tokens WHERE token=:token";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([":token" => $key]);

    if($stmt->rowCount() > 0){
        $result = $stmt->fetch();
        if($key == $result["token"]){

            // update hit q38ji7y3nosppygep984yghr
//            $sql = "UPDATE api_tokens SET hit=hit+1 WHERE api_key=:api_key";
//            $stmt = $this->db->prepare($sql);
//            $stmt->execute([":api_key" => $key]);

            return $response = $next($request, $response);
        }
    }

    return $response->withJson(["status" => "Unauthorized"], 401);

});
