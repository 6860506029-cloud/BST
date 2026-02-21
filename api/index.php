<?php
// 1. Database connection settings
// Use the information from your MariaDB terminal (Adilah_database)
$host = "localhost";
$user = "root";
$pass = "YOUR_PASSWORD"; // Change to your real MariaDB password
$db   = "Adilah_database";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Insert data when the user clicks 'Add Tree'
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['tree_name'])) {
    $name = $_POST['tree_name'];
    $stmt = $conn->prepare("INSERT INTO tree_nodes (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
}

// 3. Fetch all trees to display (ensures data stays after refresh)
$result = $conn->query("SELECT * FROM tree_nodes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BST Skill Tree (Full CRUD)</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0c10;
            --panel: #1f2833;
            --neon-blue: #66fcf1;
            --neon-green: #45a29e;
            --text: #c5c6c7;
            --accent: #e74c3c;
            --update-color: #f1c40f; /* สีเหลืองสำหรับปุ่ม Update */
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Roboto Mono', monospace;
            margin: 0; padding: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            color: var(--neon-blue);
            text-transform: uppercase;
            letter-spacing: 4px;
            text-shadow: 0 0 10px rgba(102, 252, 241, 0.5);
            margin-bottom: 10px;
        }

        .controls {
            background: var(--panel);
            padding: 15px 25px;
            border-radius: 5px;
            border-left: 5px solid var(--neon-green);
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            margin-bottom: 20px;
            z-index: 100;
        }

        input {
            background: #000;
            border: 1px solid var(--neon-green);
            color: var(--neon-blue);
            padding: 8px 12px;
            font-family: 'Roboto Mono';
            font-size: 16px;
            width: 100px;
            text-align: center;
        }

        button {
            background: transparent;
            border: 1px solid var(--neon-blue);
            color: var(--neon-blue);
            padding: 8px 15px;
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        button:hover { background: var(--neon-blue); color: #000; box-shadow: 0 0 10px var(--neon-blue); }
        
        /* ปุ่มพิเศษ */
        .btn-update { border-color: var(--update-color); color: var(--update-color); }
        .btn-update:hover { background: var(--update-color); color: #000; box-shadow: 0 0 10px var(--update-color); }

        .btn-del { border-color: var(--accent); color: var(--accent); }
        .btn-del:hover { background: var(--accent); color: #fff; box-shadow: 0 0 10px var(--accent); }

        #message { margin-top: 10px; font-size: 14px; min-height: 20px; color: #888; }

        /* พื้นที่วาดกราฟ */
        #diagram-container {
            position: relative;
            width: 95vw;
            height: 70vh;
            background: radial-gradient(circle at 10% 20%, #1f2833 0%, #0b0c10 90%);
            border: 1px solid #333;
            border-radius: 10px;
            overflow: auto;
            box-shadow: inset 0 0 50px #000;
        }

        /* SVG สำหรับวาดเส้น */
        #lines-layer {
            position: absolute;
            top: 0; left: 0;
            width: 3000px; /* ขยายพื้นที่เผื่อ Tree ยาว */
            height: 2000px;
            pointer-events: none; 
            z-index: 1;
        }

        .connection-line {
            fill: none;
            stroke: #45a29e;
            stroke-width: 2;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: drawLine 1s forwards;
        }
        
        @keyframes drawLine { to { stroke-dashoffset: 0; } }

        .node {
            width: 60px; height: 40px;
            background: rgba(11, 12, 16, 0.9);
            border: 2px solid var(--neon-blue);
            color: var(--neon-blue);
            display: flex; justify-content: center; align-items: center;
            position: absolute;
            font-weight: bold;
            font-size: 16px;
            z-index: 2;
            cursor: default;
            clip-path: polygon(10% 0, 100% 0, 100% 70%, 90% 100%, 0 100%, 0 30%);
            transition: all 0.4s ease;
        }

        .node:hover {
            background: var(--neon-blue);
            color: #000;
            transform: scale(1.1);
        }

        .node.highlight {
            border-color: #fff;
            background: var(--accent);
            color: #fff;
            box-shadow: 0 0 20px var(--accent);
            transform: scale(1.2);
            z-index: 10;
        }
        
        /* Highlight สีเหลืองสำหรับ Update */
        .node.updated {
            border-color: #fff;
            background: var(--update-color);
            color: #000;
            box-shadow: 0 0 20px var(--update-color);
            transform: scale(1.2);
            z-index: 10;
        }
    </style>
</head>
<body>

    <h1>BST : Skill Tree Edition</h1>

    <div class="controls">
        <input type="number" id="valInput" placeholder="NUMBER" onkeydown="if(event.key==='Enter') action('add')">
        <button onclick="action('add')">CREATE</button>
        <button onclick="action('find')">READ</button>
        <button class="btn-update" onclick="action('update')">UPDATE</button>
        <button class="btn-del" onclick="action('del')">DELETE</button>
        <div id="message">:: SYSTEM READY ::</div>
    </div>

    <div id="diagram-container">
        <svg id="lines-layer"></svg>
        </div>

    <script>
        // --- Core Logic (BST) ---
        class Node {
            constructor(data) {
                this.data = data;
                this.left = null; this.right = null;
                this.x = 0; this.y = 0;
            }
        }
        class BST {
            constructor() { this.root = null; }
            insert(data) {
                if(!this.root) this.root = new Node(data);
                else this._insert(this.root, new Node(data));
            }
            _insert(node, newNode) {
                if(newNode.data < node.data) {
                    if(!node.left) node.left = newNode; else this._insert(node.left, newNode);
                } else {
                    if(!node.right) node.right = newNode; else this._insert(node.right, newNode);
                }
            }
            search(node, data) {
                if(!node) return null;
                if(data < node.data) return this.search(node.left, data);
                else if(data > node.data) return this.search(node.right, data);
                else return node;
            }
            remove(data) { this.root = this._remove(this.root, data); }
            _remove(node, key) {
                if(!node) return null;
                if(key < node.data) { node.left = this._remove(node.left, key); return node; }
                else if(key > node.data) { node.right = this._remove(node.right, key); return node; }
                else {
                    if(!node.left && !node.right) return null;
                    if(!node.left) return node.right;
                    if(!node.right) return node.left;
                    let min = this._findMin(node.right);
                    node.data = min.data;
                    node.right = this._remove(node.right, min.data);
                    return node;
                }
            }
            _findMin(node) { while(node.left) node = node.left; return node; }
        }

        // --- Visualization & Actions ---
        const bst = new BST();
        const container = document.getElementById('diagram-container');
        const svgLayer = document.getElementById('lines-layer');
        const msg = document.getElementById('message');
        let nodeElements = {};

        function action(type) {
            const input = document.getElementById('valInput');
            const val = parseInt(input.value);
            
            // เช็คว่าใส่เลขหรือเปล่า (ยกเว้นตอนกดปุ่มโดยไม่ใส่เลข)
            if(isNaN(val)) {
                msg.innerText = "Error: Input Number Required.";
                return;
            }

            if(type === 'add') {
                if(bst.search(bst.root, val)) { msg.innerText = "Error: Duplicate Data"; return; }
                bst.insert(val);
                draw();
                msg.innerText = `>> CREATE: Node ${val} installed.`;
            
            } else if(type === 'find') {
                const found = bst.search(bst.root, val);
                if(found) {
                    msg.innerText = `>> READ: Target ${val} found.`;
                    highlight(val, 'highlight');
                } else msg.innerText = ">> READ: Target not found.";
            
            } else if(type === 'update') {
                // 1. หาตัวเก่าก่อน
                if(!bst.search(bst.root, val)) { 
                    msg.innerText = `>> UPDATE Failed: Old value ${val} not found.`; 
                    return; 
                }
                // 2. ถามค่าใหม่
                let newValStr = prompt(`Change value from ${val} to?`);
                let newVal = parseInt(newValStr);

                if(isNaN(newVal)) { msg.innerText = ">> UPDATE Canceled."; return; }
                if(bst.search(bst.root, newVal)) { msg.innerText = `>> UPDATE Failed: ${newVal} already exists.`; return; }

                // 3. ลบตัวเก่า -> ใส่ตัวใหม่
                bst.remove(val);
                bst.insert(newVal);
                draw();
                msg.innerText = `>> UPDATE: Changed ${val} to ${newVal}.`;
                highlight(newVal, 'updated');

            } else if(type === 'del') {
                if(!bst.search(bst.root, val)) { msg.innerText = ">> DELETE Failed: Target missing."; return; }
                bst.remove(val);
                draw();
                msg.innerText = `>> DELETE: Node ${val} removed.`;
            }

            input.value = ''; input.focus();
        }

        function draw() {
            const nodes = container.querySelectorAll('.node');
            nodes.forEach(n => n.remove());
            svgLayer.innerHTML = '';
            nodeElements = {};

            if(bst.root) {
                // Horizontal Layout: X=Depth, Y=Vertical Spread
                calculatePos(bst.root, 80, 300, 160); 
                drawLines(bst.root);
                drawNodes(bst.root);
            }
        }

        function calculatePos(node, x, y, offset) {
            node.x = x; node.y = y;
            const nextOffset = Math.max(offset / 1.8, 40);
            if(node.left) calculatePos(node.left, x + 120, y - offset, nextOffset);
            if(node.right) calculatePos(node.right, x + 120, y + offset, nextOffset);
        }

        function drawNodes(node) {
            if(!node) return;
            const el = document.createElement('div');
            el.className = 'node';
            el.innerText = node.data;
            el.style.left = (node.x - 30) + 'px';
            el.style.top = (node.y - 20) + 'px';
            container.appendChild(el);
            nodeElements[node.data] = el;
            
            drawNodes(node.left);
            drawNodes(node.right);
        }

        function drawLines(node) {
            if(!node) return;
            if(node.left) createBezier(node, node.left);
            if(node.right) createBezier(node, node.right);
            drawLines(node.left);
            drawLines(node.right);
        }

        function createBezier(parent, child) {
            const x1 = parent.x + 30; const y1 = parent.y;
            const x2 = child.x - 30; const y2 = child.y;
            const c1x = x1 + 50; const c1y = y1;
            const c2x = x2 - 50; const c2y = y2;

            const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            const d = `M ${x1} ${y1} C ${c1x} ${c1y}, ${c2x} ${c2y}, ${x2} ${y2}`;
            path.setAttribute("d", d);
            path.setAttribute("class", "connection-line");
            svgLayer.appendChild(path);
        }

        function highlight(val, className) {
            const el = nodeElements[val];
            if(el) {
                document.querySelectorAll('.node').forEach(e => {
                    e.classList.remove('highlight');
                    e.classList.remove('updated');
                });
                el.classList.add(className);
                setTimeout(() => el.classList.remove(className), 2000);
            }
        }

        // Demo Data
        [50, 30, 70, 20, 40, 60, 80].forEach(d => bst.insert(d));
        draw();
    </script>
</body>
</html>

