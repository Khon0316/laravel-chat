# laravel-chat

composer require cboden/ratchet


php artisan make:command WebSocketServer --command=websocket:init

php artisan websocket:init

var conn = new WebSocket('ws://localhost:8090');
conn.onopen = function(e) {
    console.log("Connection established!");
};

var conn = new WebSocket('wss://localhost:8091');
conn.onopen = function(e) {
    console.log("Connection established!");
};
conn.onmessage = function(e) {
    console.log(e.data);
};
conn.onmessage = function(e) {
    console.log(e.data);
};'


conn.send(JSON.stringify({command: "register", userId: 1}));
conn.send(JSON.stringify({command: "register", userId: 9}));

conn.send(JSON.stringify({command: "message", from:"9", to: "1", message: "Hello"}));
conn.send(JSON.stringify({command: "message", from:"1", to: "9", message: "Hi"}));

conn.send(JSON.stringify({command: "subscribe", channel: "global"}));
conn.send(JSON.stringify({command: "groupchat", message: "hello glob", channel: "global"}));
