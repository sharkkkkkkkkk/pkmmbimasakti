<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMA-SAKTI | Single Generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] } } } }</script>
</head>
<body class="bg-slate-50 text-slate-600 min-h-screen">

    <nav class="bg-white border-b sticky top-0 z-40 px-6 py-4 flex justify-between items-center shadow-sm">
        <h1 class="font-bold text-lg text-slate-800">BIMA-SAKTI <span class="text-blue-600">Generator</span></h1>
    </nav>

    <main class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4 space-y-5">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <h2 class="font-bold text-slate-800 mb-4">Parameter Menu</h2>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div><label class="text-xs font-bold text-slate-400">Pax</label><input type="number" id="pax" value="50" class="w-full p-2 border rounded bg-slate-50"></div>
                    <div><label class="text-xs font-bold text-slate-400">Budget</label><input type="number" id="budget" value="15000" class="w-full p-2 border rounded bg-slate-50"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div><label class="text-xs font-bold text-slate-400">Kalori</label><input type="number" id="cal" value="650" class="w-full p-2 border rounded bg-slate-50"></div>
                    <div><label class="text-xs font-bold text-slate-400">Protein</label><input type="number" id="prot" value="25" class="w-full p-2 border rounded bg-slate-50"></div>
                </div>
                <label class="text-xs font-bold text-slate-400">Note</label>
                <textarea id="pref" rows="2" class="w-full p-2 border rounded bg-slate-50 mb-3"></textarea>

                <div class="border-t pt-3 mt-3">
                    <label class="text-xs font-bold text-slate-400 mb-2 block">Stok Manual</label>
                    <div id="manualStock" class="space-y-2 text-sm"></div>
                    <button onclick="addStock()" class="text-xs text-blue-600 font-bold mt-2">+ Tambah Item</button>
                </div>
                <button onclick="generate()" id="btnGen" class="w-full mt-6 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 shadow-lg">Generate Menu</button>
            </div>
        </div>

        <div class="lg:col-span-8 relative min-h-[500px]">
            <div id="loading" class="hidden absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm rounded-2xl">
                <div class="animate-spin text-4xl text-blue-600 mb-4"><i class="fa-solid fa-circle-notch"></i></div>
                <p class="font-bold">AI sedang berpikir (Max 2 menit)...</p>
            </div>
            <div id="empty" class="h-full flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl text-slate-400">
                <i class="fa-solid fa-bowl-food text-5xl mb-4"></i><p>Siap Generate</p>
            </div>
            <div id="result" class="hidden space-y-6">
                <div class="bg-white rounded-2xl p-8 shadow-xl border border-blue-50 relative overflow-hidden">
                    <h2 id="resName" class="text-3xl font-extrabold text-slate-800 mb-2">Menu</h2>
                    <p id="resDesc" class="text-slate-500 italic mb-4">Desc...</p>
                    <div class="flex gap-6 border-t pt-4">
                        <div><p class="text-xs font-bold text-slate-400">Total</p><p id="resTotal" class="text-xl font-bold text-green-600">Rp 0</p></div>
                        <div><p class="text-xs font-bold text-slate-400">Per Pax</p><p id="resPaxCost" class="text-xl font-bold text-blue-600">Rp 0</p></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow border border-slate-100">
                        <h3 class="font-bold mb-4">Bahan</h3><div id="ingList" class="space-y-3"></div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow border border-slate-100">
                        <h3 class="font-bold mb-4">Cara Masak</h3><ol id="stepList" class="list-decimal ml-4 space-y-2"></ol>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function addStock() {
            const div = document.createElement('div');
            div.className = "flex gap-1 stock-row";
            div.innerHTML = `<input placeholder="Nama" class="st-name w-full p-1 border rounded text-xs"><input placeholder="Qty" class="st-qty w-12 p-1 border rounded text-xs text-center">`;
            document.getElementById('manualStock').appendChild(div);
        }

        async function generate() {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('result').classList.add('hidden');
            document.getElementById('empty').classList.add('hidden');
            document.getElementById('btnGen').disabled = true;

            const stocks = [];
            document.querySelectorAll('.stock-row').forEach(r => {
                const name = r.querySelector('.st-name').value;
                const qty = r.querySelector('.st-qty').value;
                if(name && qty) stocks.push({ name, qty });
            });

            const payload = {
                pax: document.getElementById('pax').value,
                budget: document.getElementById('budget').value,
                preferences: document.getElementById('pref').value,
                nutrition: { calories: document.getElementById('cal').value, protein: document.getElementById('prot').value },
                manual_stock: stocks
            };

            try {
                const req = await fetch('public/api_generate.php', {
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                // DEBUGGING LOGIC
                const rawText = await req.text();
                
                try {
                    const res = JSON.parse(rawText);
                    if(res.status === 'success') {
                        renderResult(res.result);
                    } else {
                        alert("API Error: " + res.message);
                        document.getElementById('empty').classList.remove('hidden');
                    }
                } catch(jsonErr) {
                    console.error("PHP Error:", rawText);
                    // Bersihkan tag HTML
                    const cleanErr = rawText.replace(/<[^>]*>?/gm, '');
                    alert("SERVER ERROR (PHP):\n" + cleanErr);
                    document.getElementById('empty').classList.remove('hidden');
                }

            } catch(e) {
                alert('Koneksi Gagal: ' + e.message);
                document.getElementById('empty').classList.remove('hidden');
            } finally {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('btnGen').disabled = false;
            }
        }

        function renderResult(data) {
            document.getElementById('result').classList.remove('hidden');
            document.getElementById('resName').innerText = data.menu_name;
            document.getElementById('resDesc').innerText = data.description;
            document.getElementById('resTotal').innerText = fmtRp(data.total_cost);
            document.getElementById('resPaxCost').innerText = fmtRp(data.cost_per_pax);

            const ingList = document.getElementById('ingList');
            ingList.innerHTML = '';
            data.ingredients.forEach(ing => {
                let statusColor = ing.status.includes('GUDANG') ? 'bg-green-100 text-green-700' : 'bg-blue-50 text-blue-600';
                ingList.innerHTML += `
                    <div class="flex justify-between items-center p-2 rounded border border-slate-100">
                        <div><p class="font-bold text-sm">${ing.name}</p><p class="text-xs text-slate-400">${ing.qty} ${ing.unit}</p></div>
                        <div class="text-right"><span class="text-[10px] px-2 py-1 rounded ${statusColor} font-bold">${ing.status}</span><p class="text-xs font-bold mt-1">Rp ${fmtK(ing.total)}</p></div>
                    </div>`;
            });

            const stepList = document.getElementById('stepList');
            stepList.innerHTML = '';
            data.steps.forEach(s => stepList.innerHTML += `<li class="text-sm text-slate-600">${s}</li>`);
        }

        function fmtRp(n) { return new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', minimumFractionDigits:0}).format(n); }
        function fmtK(n) { return new Intl.NumberFormat('id-ID').format(n); }
    </script>
</body>
</html>