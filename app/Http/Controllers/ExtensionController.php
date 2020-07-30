<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Traits\DateFormatter;
use Illuminate\Http\Request;
use App\Http\Requests\StoreExtension;
use Illuminate\Support\Facades\Db;

class ExtensionController extends Controller
{
    use DateFormatter;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExtension $request)
    {

        $response['type'] = 'error';
        try {
            $student_user_id = $request->student_user_id;
            $assignment_id = $request->assignment_id;
            $course_id = $request->course_id;
            /*  if (!$this->updateScorePolicy($request->course_id, $request->assignment_id, $request->student_user_id)){
                  $response['message'] = "You don't have access to that student/assignment combination.";
                  return $response;
              }*/


            $data = $request->validated();

            Extension::create(
                ['extension' => $data['extension_date'] . ' ' . $data['extension_time'],
                    'user_id' => $student_user_id,
                    'assignment_id' => $assignment_id
                ]
            );

            $response['type'] = 'success';
            $response['message'] = 'The student has been given an extension.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the extension.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {

        $extension = DB::table('extensions')
            ->where('user_id', $request->user)
            ->where('assignment_id', $request->assignment)
            ->first()
            ->extension;

        if ($extension) {
            $response['type'] = 'success';
            $response['extension_date'] = $this->getDateFromSqlTimestamp($extension);
            $response['extension_time'] = $this->getTimeFromSqlTimestamp($extension);

        }
        return $response;

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
     */
    public function edit(Extension $extension)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
     */
    public function update(StoreExtension $request, Extension $extension)
    {
        $response['type'] = 'error';
        try {
            $student_user_id = $request->student_user_id;
            $assignment_id = $request->assignment_id;
            $course_id = $request->course_id;
            /*  if (!$this->updateScorePolicy($request->course_id, $request->assignment_id, $request->student_user_id)){
                  $response['message'] = "You don't have access to that student/assignment combination.";
                  return $response;
              }*/


            $data = $request->validated();
            Extension::where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->update(['extension' => $data['extension_date'] . ' ' . $data['extension_time']]);


            $response['type'] = 'success';
            $response['message'] = 'The extension has been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Extension $extension
     * @return \Illuminate\Http\Response
     */
    public function destroy(Extension $extension)
    {
        //
    }
}
