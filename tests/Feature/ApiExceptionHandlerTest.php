<?php

test('should return 500 for unknown exceptions', function () {
    Route::get("test-route",fn () => throw new Exception("hello world"));
    $response = $this->getJson('/test-route');
   // $response->dump();
 $response->assertStatus(500);
 $response->assertJsonStructure(["success","message","error"]);
});
