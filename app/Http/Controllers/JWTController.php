<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\DataShop;
use App\Http\Requests\StoreSubmission;
use App\JWE;
use App\LtiLaunch;
use App\LtiGradePassback;
use App\Score;
use App\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\JWT;

class JWTController extends Controller
{
    use JWT;

    public function init()
    {

        $JWE = new JWE();
        $token = $JWE->encrypt('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9sb2dpbiIsImlhdCI6MTYwMjU5NTM5MSwiZXhwIjoyNDY2NTk1MzkxLCJuYmYiOjE2MDI1OTUzOTEsImp0aSI6IlV5alhWSlRzdHdmbkNyZjEiLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEifQ.-o0_89Kc5dqt58pbFGw4AktqrndDPb_L5lEmRY4Vqes');
        echo "The encrypted token: " . $token;
        echo "The decrypted token: " . $JWE->decrypt($token);
    }

    public function signWithNewSecret()
    {
        //encoding...
        //


        $payload = auth()->payload();
        print_r($payload->toArray()); // current user info
        \JWTAuth::getJWTProvider()->setSecret('secret'); //change the secret
        $claims = ['foo' => 'bar'];//create the claims
        $token = \JWTAuth::getJWTProvider()->encode(array_merge($claims, $payload->toArray())); //create the token


        //set the same secret for encoding
        $secret = file_get_contents(base_path() . '/JWE/webwork');
        \JWTAuth::getJWTProvider()->setSecret($secret);
        //$decoded = \JWTAuth::getJWTProvider()->decode($token);
        auth()->setToken($token)->getPayload();
        var_dump(auth()->user());
    }

    public function validateToken(string $content)
    {
        //Webwork should post the answerJWT with Authorization using the Adapt JWT
        $response['type'] = 'error';
        try {
            if (!auth()->setToken($content)->getPayload()) {
                $response['message'] = 'User not found';
            } else {
                $response['type'] = 'success';
            }

        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }
        return $response;
    }


    public function processAnswerJWT(Request $request)
    {

        $JWE = new JWE();
        //$referer = $request->headers->get('referer');//will use this to determine the technology
        $technology = 'webwork';

        $secret = $JWE->getSecret($technology);
        \JWTAuth::getJWTProvider()->setSecret($secret);
        $content = $request->getContent();
        Log::info('Content:' . $content);
        $answerJWT = $this->getPayload($content);
        //TODO: Verify anwserJWT:  submit a question, verify the signature then check problem JWT (shared secret)
        Log::info('Answer JWT:' . json_encode($answerJWT));
        if (!isset($answerJWT->problemJWT)) {
            $message = "You are missing the problemJWT in your answerJWT!";
            return json_encode(['type' => 'error', 'message' => $message]);
        }

        $jwe = new JWE();
        $problemJWT = $jwe->decrypt($answerJWT->problemJWT, $technology);
      //  Log::info('Problem JWT:' .json_encode($problemJWT));
        $token = \JWTAuth::getJWTProvider()->encode(json_decode($problemJWT, true));
        Log::info($token);
        $response = $this->validateToken($token);
        if ($response['type'] === 'error') {
            return json_encode($response);
        }


//if the token isn't formed correctly return a message

        $problemJWT = json_decode($problemJWT);
        $missing_properties = !(
            isset($problemJWT->adapt) &&
            isset($problemJWT->adapt->assignment_id) &&
            isset($problemJWT->adapt->question_id) &&
            isset($problemJWT->adapt->technology));
        if ($missing_properties) {
            $message = "The problemJWT has an incorrect structure.  Please contact us for assistance.";
            return json_encode(['type' => 'error', 'message' => $message]);
        }

        if (!in_array($problemJWT->adapt->technology, ['webwork', 'imathas'])) {
            $message = $problemJWT->adapt->technology . " is not an accepted technology.  Please contact us for assistance.";
            return json_encode(['type' => 'error', 'message' => $message]);
        }

        //good to go!
        $request = new storeSubmission();
        $request['assignment_id'] = $problemJWT->adapt->assignment_id;
        $request['question_id'] = $problemJWT->adapt->question_id;
        $request['technology'] = $problemJWT->adapt->technology;
        $request['submission'] = $answerJWT;

        if (($request['technology'] === 'webwork') && $answerJWT->score === null) {
            $response['message'] = 'Score field was null.';
            $response['type'] = 'error';
            return $response;
        }
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), new Assignment(), new Score(), new LtiLaunch(), new LtiGradePassback(), new DataShop());
    }

}

