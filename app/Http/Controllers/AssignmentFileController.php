<?php

namespace App\Http\Controllers;

use App\AssignmentFile;
use App\Assignment;
use Illuminate\Http\Request;

class AssignmentFileController extends Controller
{

    public function getAssignmentFilesByAssignment(Request $request, Assignment $assignment){

        return $assignment->assignmentFiles;

    }
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\AssignmentFile  $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function show(AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AssignmentFile  $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function edit(AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AssignmentFile  $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssignmentFile $assignmentFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AssignmentFile  $assignmentFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssignmentFile $assignmentFile)
    {
        //
    }
}
