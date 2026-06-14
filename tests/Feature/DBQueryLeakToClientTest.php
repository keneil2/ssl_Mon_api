<?php

use App\Models\User;

test('sql query exceptions should not leak to client', function () {
    Route::get("/test-sql-leak",function(){
    User::create(["name"=>"test"]);
    });
    
    $response = $this->get('/test-sql-leak');
    $response->assertJsonStructure(["message","success","error"]);
    $response->assertJson(["message"=>"A database error occurred. Please try again"]);
    $response->assertStatus(500);
});
