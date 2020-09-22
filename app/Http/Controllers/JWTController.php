<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Http\Requests\StoreSubmission;
use App\JWTModel;
use App\Score;
use App\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class JWTController extends Controller
{


    public function init()
    {
        $JWTModel = new JWTModel();
        $token = $JWTModel->encode('My really secret payload that only Henry knows.');
        echo "The encrypted token: " . $token;
        echo "The decrypted token: " . $JWTModel->decode($token);
    }

    public function validateToken()
    {
        //Webwork should post the answerJWT with Authorization using the Adapt JWT
        try {
            if (!$user = \JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());

        }
        return \JWTAuth::parseToken()->getPayload();
    }


    public function processAnswerJWT()
    {
        $payload = $this->validateToken();//validate from the Header
        // return $payload;
        //$answerJWT = $request->answerJWT;
        $answerJwt = base64_decode('eyJleHAiOjI0NjQ3OTg1NTIsInByb2JsZW1KV1QiOiJleUowZVhBaU9pSktWMVFpTENKaGJHY2lPaUpJVXpJMU5pSjkuZXlKcGMzTWlPaUpvZEhSd09sd3ZYQzh4TWpjdU1DNHdMakU2T0RBd01Gd3ZZWEJwWEM5aGMzTnBaMjV0Wlc1MGMxd3ZORnd2Y1hWbGMzUnBiMjV6WEM5MmFXVjNJaXdpYVdGMElqb3hOakF3TnprNE5UVXlMQ0psZUhBaU9qSTBOalEzT1RnMU5USXNJbTVpWmlJNk1UWXdNRGM1T0RVMU1pd2lhblJwSWpvaVVtWXdNbnBqVTJ4T1F6SmphR0ZDY2lJc0luTjFZaUk2TVRNc0luQnlkaUk2SWpnM1pUQmhaakZsWmpsbVpERTFPREV5Wm1SbFl6azNNVFV6WVRFMFpUQmlNRFEzTlRRMllXRWlMQ0poWkdGd2RDSTZleUpoYzNOcFoyNXRaVzUwWDJsa0lqbzBMQ0p4ZFdWemRHbHZibDlwWkNJNk9UQTVOaXdpZEdWamFHNXZiRzluZVNJNkluZGxZbmR2Y21zaWZTd2lhVzFoZEdoaGN5STZJaUlzSW5kbFluZHZjbXNpT25zaWNISnZZbXhsYlZObFpXUWlPaUl4TWpNME5UWTNJaXdpWTI5MWNuTmxTVVFpT2lKa1lXVnRiMjVmWTI5MWNuTmxJaXdpZFhObGNrbEVJam9pWkdGbGJXOXVJaXdpWTI5MWNuTmxYM0JoYzNOM2IzSmtJam9pWkdGbGJXOXVJaXdpYzJodmQxTjFiVzFoY25raU9qRXNJbVJwYzNCc1lYbE5iMlJsSWpvaVRXRjBhRXBoZUNJc0lteGhibWQxWVdkbElqb2laVzRpTENKdmRYUndkWFJtYjNKdFlYUWlPaUpzYVdKeVpYUmxlSFJ6SWl3aWMyOTFjbU5sUm1sc1pWQmhkR2dpT2lKTWFXSnlZWEo1WEM5U2IyTm9aWE4wWlhKY0wzTmxkRlpsWTBaMWJtTjBhVzl1TWtOMWNuWmhkSFZ5WlZ3dmRYSmZkbU5mTkY4ekxuQm5JaXdpWVc1emQyVnljMU4xWW0xcGRIUmxaQ0k2SWpBaUxDSndjbTlpYkdWdFZWVkpSQ0k2T0RJeWZTd2labTl5YlY5aFkzUnBiMjVmZFhKc0lqb2lhSFIwY0hNNlhDOWNMMlJsYlc4dWQyVmlkMjl5YXk1eWIyTm9aWE4wWlhJdVpXUjFYQzkzWldKM2IzSnJNbHd2YUhSdGJESjRiV3dpZlEuVVlETVc2c1prbFhGTDlXb3RkbDA2MmhMekU0S19Kd1ZBQ01kcV8yTGJxWSIsInNlc3Npb25KV1QiOiJleUpoYkdjaU9pSklVekkxTmlKOS5leUpoYm5OM1pYSnpVM1ZpYldsMGRHVmtJam94ZlEubWlFRkhlQjV3VkstOUpIbXBHbmh0TndEaXoxdnRuY1ZhMVZPNnNBM0hmQSIsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEiLCJuYmYiOjE2MDA3OTg1NTIsIm5hbWUiOiIiLCJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2Fzc2lnbm1lbnRzLzQvcXVlc3Rpb25zL3ZpZXciLCJzY29yZSI6eyI2Ijp7ImFuc3dlciI6eyJkb25lIjoiMSIsInByZXZpZXdfdGV4dF9zdHJpbmciOiIiLCJzaG93VW5pb25SZWR1Y2VXYXJuaW5ncyI6IjEiLCJjb3JyZWN0X2FucyI6IjAiLCJ1cFRvQ29uc3RhbnQiOiIwIiwiX2ZpbHRlcl9uYW1lIjoiZGVyZWZlcmVuY2VfYXJyYXlfYW5zIiwic2hvd1R5cGVXYXJuaW5ncyI6IjEiLCJkZWJ1ZyI6IjAiLCJpZ25vcmVJbmZpbml0eSI6IjEiLCJwcmV2aWV3X2xhdGV4X3N0cmluZyI6IiIsIm9yaWdpbmFsX3N0dWRlbnRfYW5zIjoiIiwic3R1ZGVudF9hbnMiOiIiLCJhbnNfbWVzc2FnZSI6IiIsInNob3dEb21haW5FcnJvcnMiOiIxIiwic3R1ZGVudHNNdXN0UmVkdWNlVW5pb25zIjoiMSIsImlnbm9yZVN0cmluZ3MiOiIxIiwiZXJyb3JfZmxhZyI6IiIsInNjb3JlIjoiMCIsImFuc19sYWJlbCI6IkFuU3dFcjAwMDYiLCJkaWFnbm9zdGljcyI6IiIsImNvcnJlY3RfYW5zX2xhdGV4X3N0cmluZyI6IjAiLCJlcnJvcl9tZXNzYWdlIjoiIiwiY29ycmVjdF92YWx1ZSI6IjAiLCJ0eXBlIjoiVmFsdWUgKEZvcm11bGEpIiwiYW5zX25hbWUiOiJBblN3RXIwMDA2Iiwic2hvd0VxdWFsRXJyb3JzIjoiMSJ9LCJzY29yZSI6IjAiLCJhbnNfaWQiOiJBblN3RXIwMDA2In0sIjQiOnsiYW5zd2VyIjp7ImRvbmUiOiIxIiwicHJldmlld190ZXh0X3N0cmluZyI6IiIsInNob3dVbmlvblJlZHVjZVdhcm5pbmdzIjoiMSIsImNvcnJlY3RfYW5zIjoiLSAoLTYpKioyICogY29zKCAtNiAqIHQgKSIsInVwVG9Db25zdGFudCI6IjAiLCJfZmlsdGVyX25hbWUiOiJkZXJlZmVyZW5jZV9hcnJheV9hbnMiLCJzaG93VHlwZVdhcm5pbmdzIjoiMSIsImRlYnVnIjoiMCIsImlnbm9yZUluZmluaXR5IjoiMSIsInByZXZpZXdfbGF0ZXhfc3RyaW5nIjoiIiwib3JpZ2luYWxfc3R1ZGVudF9hbnMiOiIiLCJzdHVkZW50X2FucyI6IiIsImFuc19tZXNzYWdlIjoiIiwic2hvd0RvbWFpbkVycm9ycyI6IjEiLCJzdHVkZW50c011c3RSZWR1Y2VVbmlvbnMiOiIxIiwiaWdub3JlU3RyaW5ncyI6IjEiLCJlcnJvcl9mbGFnIjoiIiwic2NvcmUiOiIwIiwiYW5zX2xhYmVsIjoiQW5Td0VyMDAwNCIsImRpYWdub3N0aWNzIjoiIiwiY29ycmVjdF9hbnNfbGF0ZXhfc3RyaW5nIjoiLTM2XFxjb3NcXCFcXGxlZnQoLTZ0XFxyaWdodCkiLCJlcnJvcl9tZXNzYWdlIjoiIiwiY29ycmVjdF92YWx1ZSI6Ii0zNipjb3MoLTYqdCkiLCJ0eXBlIjoiVmFsdWUgKEZvcm11bGEpIiwiYW5zX25hbWUiOiJBblN3RXIwMDA0Iiwic2hvd0VxdWFsRXJyb3JzIjoiMSJ9LCJzY29yZSI6IjAiLCJhbnNfaWQiOiJBblN3RXIwMDA0In0sIjEiOnsiYW5zd2VyIjp7ImRvbmUiOiIiLCJzdHVkZW50X3ZhbHVlIjoiMiIsInByZXZpZXdfdGV4dF9zdHJpbmciOiIyIiwiaXNQcmV2aWV3IjoiIiwic2hvd1VuaW9uUmVkdWNlV2FybmluZ3MiOiIxIiwiY21wX2NsYXNzIjoiYSBGb3JtdWxhIHRoYXQgcmV0dXJucyBhIE51bWJlciIsImNvcnJlY3RfYW5zIjoiLSAtNiAqIHNpbiggLTYgKiB0ICkiLCJ1cFRvQ29uc3RhbnQiOiIwIiwiZGVidWciOiIwIiwic2hvd1R5cGVXYXJuaW5ncyI6IjEiLCJfZmlsdGVyX25hbWUiOiJkZXJlZmVyZW5jZV9hcnJheV9hbnMiLCJzdHVkZW50X2Zvcm11bGEiOiIyIiwiaWdub3JlSW5maW5pdHkiOiIxIiwicHJldmlld19sYXRleF9zdHJpbmciOiIyIiwicHJldl9hbnMiOiIiLCJvcmlnaW5hbF9zdHVkZW50X2FucyI6IjIiLCJhbnNfbWVzc2FnZSI6IiIsInN0dWRlbnRfYW5zIjoiMiIsImlnbm9yZVN0cmluZ3MiOiIxIiwic3R1ZGVudHNNdXN0UmVkdWNlVW5pb25zIjoiMSIsInNob3dEb21haW5FcnJvcnMiOiIxIiwiZXJyb3JfZmxhZyI6IiIsInNjb3JlIjoiMCIsImFuc19sYWJlbCI6IkFuU3dFcjAwMDEiLCJkaWFnbm9zdGljcyI6IiIsImNvcnJlY3RfYW5zX2xhdGV4X3N0cmluZyI6IjZcXHNpblxcIVxcbGVmdCgtNnRcXHJpZ2h0KSIsImVycm9yX21lc3NhZ2UiOiIiLCJjb3JyZWN0X3ZhbHVlIjoiNipzaW4oLTYqdCkiLCJ0eXBlIjoiVmFsdWUgKEZvcm11bGEpIiwiYW5zX25hbWUiOiJBblN3RXIwMDAxIiwic2hvd0VxdWFsRXJyb3JzIjoiMSJ9LCJzY29yZSI6IjAiLCJhbnNfaWQiOiJBblN3RXIwMDAxIn0sIjMiOnsiYW5zd2VyIjp7ImRvbmUiOiIiLCJzdHVkZW50X3ZhbHVlIjoiMyIsInByZXZpZXdfdGV4dF9zdHJpbmciOiIzIiwiaXNQcmV2aWV3IjoiIiwic2hvd1VuaW9uUmVkdWNlV2FybmluZ3MiOiIxIiwiY21wX2NsYXNzIjoiYSBGb3JtdWxhIHRoYXQgcmV0dXJucyBhIE51bWJlciIsImNvcnJlY3RfYW5zIjoiMiIsInVwVG9Db25zdGFudCI6IjAiLCJkZWJ1ZyI6IjAiLCJzaG93VHlwZVdhcm5pbmdzIjoiMSIsIl9maWx0ZXJfbmFtZSI6ImRlcmVmZXJlbmNlX2FycmF5X2FucyIsInN0dWRlbnRfZm9ybXVsYSI6IjMiLCJpZ25vcmVJbmZpbml0eSI6IjEiLCJwcmV2aWV3X2xhdGV4X3N0cmluZyI6IjMiLCJwcmV2X2FucyI6IiIsIm9yaWdpbmFsX3N0dWRlbnRfYW5zIjoiMyIsImFuc19tZXNzYWdlIjoiIiwic3R1ZGVudF9hbnMiOiIzIiwiaWdub3JlU3RyaW5ncyI6IjEiLCJzdHVkZW50c011c3RSZWR1Y2VVbmlvbnMiOiIxIiwic2hvd0RvbWFpbkVycm9ycyI6IjEiLCJlcnJvcl9mbGFnIjoiIiwic2NvcmUiOiIwIiwiYW5zX2xhYmVsIjoiQW5Td0VyMDAwMyIsImRpYWdub3N0aWNzIjoiIiwiY29ycmVjdF9hbnNfbGF0ZXhfc3RyaW5nIjoiMiIsImVycm9yX21lc3NhZ2UiOiIiLCJjb3JyZWN0X3ZhbHVlIjoiMiIsInR5cGUiOiJWYWx1ZSAoRm9ybXVsYSkiLCJhbnNfbmFtZSI6IkFuU3dFcjAwMDMiLCJzaG93RXF1YWxFcnJvcnMiOiIxIn0sInNjb3JlIjoiMCIsImFuc19pZCI6IkFuU3dFcjAwMDMifSwiMiI6eyJhbnN3ZXIiOnsiZG9uZSI6IiIsInN0dWRlbnRfdmFsdWUiOiIzIiwicHJldmlld190ZXh0X3N0cmluZyI6IjMiLCJpc1ByZXZpZXciOiIiLCJzaG93VW5pb25SZWR1Y2VXYXJuaW5ncyI6IjEiLCJjbXBfY2xhc3MiOiJhIEZvcm11bGEgdGhhdCByZXR1cm5zIGEgTnVtYmVyIiwiY29ycmVjdF9hbnMiOiItNiAqIGNvcyggLTYgKiB0ICkiLCJ1cFRvQ29uc3RhbnQiOiIwIiwiZGVidWciOiIwIiwic2hvd1R5cGVXYXJuaW5ncyI6IjEiLCJfZmlsdGVyX25hbWUiOiJkZXJlZmVyZW5jZV9hcnJheV9hbnMiLCJzdHVkZW50X2Zvcm11bGEiOiIzIiwiaWdub3JlSW5maW5pdHkiOiIxIiwicHJldmlld19sYXRleF9zdHJpbmciOiIzIiwicHJldl9hbnMiOiIiLCJvcmlnaW5hbF9zdHVkZW50X2FucyI6IjMiLCJhbnNfbWVzc2FnZSI6IiIsInN0dWRlbnRfYW5zIjoiMyIsImlnbm9yZVN0cmluZ3MiOiIxIiwic3R1ZGVudHNNdXN0UmVkdWNlVW5pb25zIjoiMSIsInNob3dEb21haW5FcnJvcnMiOiIxIiwiZXJyb3JfZmxhZyI6IiIsInNjb3JlIjoiMCIsImFuc19sYWJlbCI6IkFuU3dFcjAwMDIiLCJkaWFnbm9zdGljcyI6IiIsImNvcnJlY3RfYW5zX2xhdGV4X3N0cmluZyI6Ii02XFxjb3NcXCFcXGxlZnQoLTZ0XFxyaWdodCkiLCJlcnJvcl9tZXNzYWdlIjoiIiwiY29ycmVjdF92YWx1ZSI6Ii02KmNvcygtNip0KSIsInR5cGUiOiJWYWx1ZSAoRm9ybXVsYSkiLCJhbnNfbmFtZSI6IkFuU3dFcjAwMDIiLCJzaG93RXF1YWxFcnJvcnMiOiIxIn0sInNjb3JlIjoiMCIsImFuc19pZCI6IkFuU3dFcjAwMDIifSwiNSI6eyJhbnN3ZXIiOnsiZG9uZSI6IjEiLCJwcmV2aWV3X3RleHRfc3RyaW5nIjoiIiwic2hvd1VuaW9uUmVkdWNlV2FybmluZ3MiOiIxIiwiY29ycmVjdF9hbnMiOiItICgtNikqKjIgKiBzaW4oIC02ICogdCApIiwidXBUb0NvbnN0YW50IjoiMCIsIl9maWx0ZXJfbmFtZSI6ImRlcmVmZXJlbmNlX2FycmF5X2FucyIsInNob3dUeXBlV2FybmluZ3MiOiIxIiwiZGVidWciOiIwIiwiaWdub3JlSW5maW5pdHkiOiIxIiwicHJldmlld19sYXRleF9zdHJpbmciOiIiLCJvcmlnaW5hbF9zdHVkZW50X2FucyI6IiIsInN0dWRlbnRfYW5zIjoiIiwiYW5zX21lc3NhZ2UiOiIiLCJzaG93RG9tYWluRXJyb3JzIjoiMSIsInN0dWRlbnRzTXVzdFJlZHVjZVVuaW9ucyI6IjEiLCJpZ25vcmVTdHJpbmdzIjoiMSIsImVycm9yX2ZsYWciOiIiLCJzY29yZSI6IjAiLCJhbnNfbGFiZWwiOiJBblN3RXIwMDA1IiwiZGlhZ25vc3RpY3MiOiIiLCJjb3JyZWN0X2Fuc19sYXRleF9zdHJpbmciOiItMzZcXHNpblxcIVxcbGVmdCgtNnRcXHJpZ2h0KSIsImVycm9yX21lc3NhZ2UiOiIiLCJjb3JyZWN0X3ZhbHVlIjoiLTM2KnNpbigtNip0KSIsInR5cGUiOiJWYWx1ZSAoRm9ybXVsYSkiLCJhbnNfbmFtZSI6IkFuU3dFcjAwMDUiLCJzaG93RXF1YWxFcnJvcnMiOiIxIn0sInNjb3JlIjoiMCIsImFuc19pZCI6IkFuU3dFcjAwMDUifX0sInN1YiI6MTMsImlhdCI6MTYwMDc5ODU1MiwianRpIjoiUmYwMnpjU2xOQzJjaGFCciJ9');


        $data = json_decode($payload, true);

        $request = new StoreSubmission();
        $request['assignment_id'] = $data['adapt']['assignment_id'];
        $request['question_id'] = $data['adapt']['question_id'];
        $request['technology'] = $data['adapt']['technology'];
        $request['submission'] = $answerJwt;

        $Submission = new Submission();
        return $Submission->store($request, new Submission(), new Assignment(), new Score());


    }


}
