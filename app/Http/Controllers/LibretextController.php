<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LibretextController extends Controller
{
    /**
     * @param string $library
     * @param int $pageId
     * @param Question $question
     * @return array|string[]
     * @throws Exception
     */
    public function getLocallySavedPageContents(string $library, int $pageId, Question $question)
    {

        try {
            $authorized = Gate::inspect('viewByPageId', [$question, $pageId]);
            if (!$authorized->allowed()) {
                if (\App::runningUnitTests()) {
                    return ['message' => $authorized->message()];
                }
                echo $authorized->message();
            } else {
                if (\App::runningUnitTests()) {
                    return ['message' => 'authorized'];
                }

                //if AWS, use EFS
                $efs_dir = '/mnt/local/';
                $is_efs = is_dir($efs_dir);
                $storage_path = $is_efs
                    ? $efs_dir
                    : Storage::disk('local')->getAdapter()->getPathPrefix();

                $file = "{$storage_path}{$library}/{$pageId}.php";
                if (!is_dir($storage_path . $library)) {
                    mkdir($storage_path . $library);
                }
             /**   if (!file_exists($file)) {
                    $contents = Storage::disk('s3')->get("{$library}/{$pageId}.php");
                    if ($is_efs) {
                        if (!file_exists("{$efs_dir}libretext.config.php")) {
                            file_put_contents("{$efs_dir}libretext.config.php", Storage::disk('s3')->get("libretext.config.php"));
                        }
                        $contents = str_replace("require_once(__DIR__ . '/../libretext.config.php');",
                            'require_once("' . $efs_dir . 'libretext.config.php");', $contents);
                    }
                    file_put_contents($file, $contents);
                }**/

                    $contents = Storage::disk('s3')->get("{$library}/{$pageId}.php");
                    if ($is_efs) {
                        if (!file_exists("{$efs_dir}libretext.config.php")) {
                            file_put_contents("{$efs_dir}libretext.config.php", Storage::disk('s3')->get("libretext.config.php"));
                        }
                        $contents = str_replace("require_once(__DIR__ . '/../libretext.config.php');",
                            'require_once("' . $efs_dir . 'libretext.config.php");', $contents);
                    }
                    file_put_contents($file, $contents);


                require_once($file);

            }
        } catch (Exception $e) {
            echo "We were not able to retrieve Page Id $pageId from the $library library.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
