<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Support\Facades\Storage;

// use Google\Service\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test/drive/storage', function(){
    Storage::disk('google')->put('test.txt', 'Hello from Laravel');
});

Route::get('/google-drive/auth', function () {
    $client = new Google_Client();
    $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
    $client->setRedirectUri(route('google.drive.callback'));
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->addScope(Google_Service_Drive::DRIVE);

    $authUrl = $client->createAuthUrl();

    return redirect($authUrl);
})->name('google.drive.auth');

Route::get('/google-drive/callback', function (Request $request) {
    if (!$request->has('code')) {
        return 'Authorization code missing.';
    }

    $client = new Google_Client();
    $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
    $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
    $client->setRedirectUri(route('google.drive.callback'));

    $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

    if (isset($token['error'])) {
        return 'Error fetching token: ' . $token['error_description'];
    }

    $refreshToken = $token['refresh_token'] ?? null;

    if (!$refreshToken) {
        return 'Refresh token not returned. Make sure "access_type" is "offline" and "prompt" is "consent".';
    }

    return response()->json([
        'access_token' => $token['access_token'],
        'refresh_token' => $refreshToken,
        'expires_in' => $token['expires_in'],
        'token_type' => $token['token_type'],
    ]);
})->name('google.drive.callback');
