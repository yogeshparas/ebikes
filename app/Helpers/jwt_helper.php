<?php

use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function getJWTFromRequest( $authenticationHeader ): string {
    if (empty($authenticationHeader)) { //JWT is absent
        throw new Exception('Missing or invalid JWT in request');
    }
    //JWT is sent from client in the format Bearer XXXXXXXXX
    return explode( ' ', $authenticationHeader )[1];
}

function validateJWTFromRequest( string $encodedToken ) { 
    try {
        $encodedToken   = getJWTFromRequest($encodedToken);
        $decodedToken   = JWT::decode( $encodedToken, new Key( Services::getSecretKey(), 'HS256' ) );
        return $decodedToken->userId;
    } catch ( Exception $e ) {
        return $e->getMessage();
        //return false;
    }
}

function getSignedJWTForUser( string $userId ) {
    $issuedAtTime       = time();
    $tokenTimeToLive    = getenv( 'JWT_TIME_TO_LIVE' );
    $tokenExpiration    = $issuedAtTime + $tokenTimeToLive;
    $payload = array (
        'userId'    => $userId,
        'iat'       => $issuedAtTime,
        'exp'       => $tokenExpiration
    );
    return JWT::encode( $payload, Services::getSecretKey(), 'HS256' );
}
