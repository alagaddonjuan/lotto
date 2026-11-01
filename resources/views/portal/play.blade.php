<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tenant->name }} | WhatsApp Player Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-bubble-received {
            background: #1e293b;
            border-bottom-left-radius: 4px;
        }
        .chat-bubble-sent {
            background: #065f46;
            border-bottom-right-radius: 4px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- Header Bar -->
    <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex flex-wrap justify-between items-center gap-4 sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center font-black text-xl text-slate-950 shadow-lg shadow-emerald-500/20">
                <i class="fa-brands fa-whatsapp text-2xl"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-bold text-base leading-tight text-white">{{ $tenant->name }} Play Kiosk</h1>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-mono flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        NLRC Licensed
                    </span>
                </div>
                <p class="text-xs text-slate-400">Interactive WhatsApp Mobile Lottery & Web Play Portal</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-slate-950 border border-slate-800 px-4 py-2 rounded-xl text-right">
                <div class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Player Wallet</div>
                <div class="text-sm font-black text-emerald-400 font-mono" id="headerWalletBal">
                    {{ $tenant->currency }} {{ number_format($walletBal, 2) }}
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 transition flex items-center gap-2">
                <i class="fa-solid fa-chart-line"></i>
                <span class="hidden sm:inline">Operator Dashboard</span>
            </a>
        </div>
    </header>

    <div class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Newcomer Registration & Player Profile (5 cols) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Newcomer Fast Registration Card -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-sm text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-emerald-400"></i>
                        Newcomer Registration
                    </h2>
                    <span class="text-[11px] bg-emerald-500/10 text-emerald-400 font-semibold px-2 py-0.5 rounded-full">
                        +₦4,500 Bonus
                    </span>
                </div>

                <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                    Register a new player in 5 seconds to test WhatsApp onboarding, wallet balance, and lotto draws.
                </p>

                <form id="regForm" onsubmit="handleRegistration(event)" class="space-y-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 uppercase tracking-wider mb-1">Player Full Name</label>
                        <input type="text" id="regName" required placeholder="e.g. Chukwudi Okafor"
                            value="{{ $player->name ?? '' }}"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 uppercase tracking-wider mb-1">WhatsApp Phone</label>
                            <input type="text" id="regPhone" required placeholder="2348031234567"
                                value="{{ $player->phone ?? $defaultPhone }}"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 placeholder-slate-600 font-mono focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 uppercase tracking-wider mb-1">4-Digit Security PIN</label>
                            <input type="password" id="regPin" required maxlength="4" placeholder="1234" value="1234"
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-100 placeholder-slate-600 font-mono tracking-widest focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <button type="submit" id="regBtn"
                        class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 font-bold text-xs text-slate-950 shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-gift"></i>
                        <span>Register & Load ₦4,500 Trial Balance</span>
                    </button>
                </form>

                <div id="regAlert" class="mt-3 hidden p-3 rounded-xl text-xs"></div>
            </div>

            <!-- Instant Fund Wallet Card -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-bold text-sm text-white flex items-center gap-2">
                        <i class="fa-solid fa-building-columns text-cyan-400"></i>
                        Dedicated Virtual Account Top-Up
                    </h3>
                    <span class="text-[10px] text-slate-400 font-mono">DVA Direct</span>
                </div>
                <p class="text-xs text-slate-400 mb-4">
                    Simulate an instant bank transfer deposit to test automatic wallet credit.
                </p>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <button type="button" onclick="fundAmount(2000)" class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-xs font-bold text-slate-200 transition">
                        +₦2,000
                    </button>
                    <button type="button" onclick="fundAmount(5000)" class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-xs font-bold text-emerald-400 transition">
                        +₦5,000
                    </button>
                    <button type="button" onclick="fundAmount(10000)" class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 text-xs font-bold text-purple-400 transition">
                        +₦10,000
                    </button>
                </div>
            </div>

            <!-- Recent Bet Slips (Digital Receipts) -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl">
                <h3 class="font-bold text-sm text-white mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-amber-400"></i>
                    Recent Bet Receipts
                </h3>
                <div id="receiptsList" class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                    @forelse($recentTickets as $ticket)
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800/80 text-xs">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-mono font-bold text-white">{{ $ticket->ticket_reference }}</span>
                                <span class="text-emerald-400 font-bold">{{ $tenant->currency }} {{ number_format($ticket->stake_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-400">
                                <span>Game: <strong class="text-slate-300">{{ $ticket->game_code }}</strong></span>
                                <span class="font-mono text-[10px] text-slate-500">{{ substr($ticket->hash, 0, 10) }}...</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-xs italic py-4 text-center">
                            No tickets placed yet. Use the WhatsApp chat simulator on the right to place a bet!
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right Column: Interactive WhatsApp Web Simulator (7 cols) -->
        <div class="lg:col-span-7 flex flex-col bg-slate-900/90 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden min-h-[640px]">

            <!-- WhatsApp Chat Header -->
            <div class="bg-slate-950 px-6 py-4 border-b border-slate-800 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center font-black text-white text-lg">
                            M
                        </div>
                        <span class="w-3 h-3 rounded-full bg-emerald-400 border-2 border-slate-950 absolute bottom-0 right-0"></span>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-white flex items-center gap-1.5">
                            <span>Modern Lottery Bot</span>
                            <i class="fa-solid fa-circle-check text-emerald-400 text-xs" title="Verified Business"></i>
                        </div>
                        <div class="text-[11px] text-emerald-400 font-mono">Official WhatsApp Gateway</div>
                    </div>
                </div>

                <!-- Fast Trigger Actions -->
                <div class="flex items-center gap-2">
                    <button onclick="sendQuickMessage('MENU')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 transition">
                        Menu
                    </button>
                    <button onclick="sendQuickMessage('BAL')" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-300 transition">
                        Balance
                    </button>
                    <button onclick="sendQuickMessage('RESET')" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-[11px] text-rose-400 border border-rose-500/20 transition">
                        Reset
                    </button>
                </div>
            </div>

            <!-- Chat Message Stream -->
            <div id="chatStream" class="flex-1 p-6 space-y-4 overflow-y-auto bg-slate-950/40 min-h-[420px]">
                <!-- Initial Bot Message -->
                <div class="flex items-start gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-emerald-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-1">
                        ML
                    </div>
                    <div class="chat-bubble-received text-slate-100 text-xs p-4 rounded-2xl max-w-[85%] whitespace-pre-line shadow-sm border border-slate-800/60 leading-relaxed font-sans">
🎰 *Welcome to Modern Lottery WhatsApp Play!* 👋
Win up to ₦10,000,000 daily directly on WhatsApp! 🎯

*Main Menu:*
👉 *1* - Play Lotto 5/90 (Daily National)
👉 *2* - Fast 3 (Instant Draw)
👉 *3* - Keno 20/80 Lucky Numbers
👉 *4* - Check Balance & Winnings
👉 *5* - Fund Wallet (Instant Bank Transfer)

_Reply with a number or type a command like "KENO 500"_
                    </div>
                </div>
            </div>

            <!-- Quick Action Chips -->
            <div class="px-4 py-2 bg-slate-950/80 border-t border-slate-800/80 flex gap-2 overflow-x-auto text-[11px]">
                <button onclick="sendQuickMessage('1')" class="px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-200 transition shrink-0">
                    1️⃣ Lotto 5/90
                </button>
                <button onclick="sendQuickMessage('2')" class="px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-200 transition shrink-0">
                    2️⃣ Fast 3
                </button>
                <button onclick="sendQuickMessage('3')" class="px-3 py-1.5 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-200 transition shrink-0">
                    3️⃣ Keno
                </button>
                <button onclick="sendQuickMessage('KENO QP 300 1234')" class="px-3 py-1.5 rounded-full bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 hover:bg-emerald-900/50 transition shrink-0 font-mono">
                    ⚡ Quick Bet: Keno ₦300
                </button>
            </div>

            <!-- Chat Input Form -->
            <form onsubmit="handleSendMessage(event)" class="p-4 bg-slate-950 border-t border-slate-800 flex gap-3 items-center">
                <input type="text" id="chatInput" placeholder="Type a message (e.g. 1, 1.1, KENO 500, BAL)..."
                    class="flex-1 bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">

                <button type="submit" id="sendBtn"
                    class="w-11 h-11 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 flex items-center justify-center font-bold text-sm shadow-md shadow-emerald-500/20 transition">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>

    </div>

    <script>
        const phoneInput = document.getElementById('regPhone');
        const chatInput = document.getElementById('chatInput');
        const chatStream = document.getElementById('chatStream');
        const walletHeader = document.getElementById('headerWalletBal');

        function appendMessage(text, isSent = false) {
            const row = document.createElement('div');
            row.className = isSent ? 'flex justify-end' : 'flex items-start gap-2.5';

            if (isSent) {
                row.innerHTML = `
                    <div class="chat-bubble-sent text-white text-xs p-3.5 rounded-2xl max-w-[85%] whitespace-pre-line shadow-sm leading-relaxed">
                        ${escapeHtml(text)}
                    </div>
                `;
            } else {
                row.innerHTML = `
                    <div class="w-7 h-7 rounded-full bg-emerald-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 mt-1">
                        ML
                    </div>
                    <div class="chat-bubble-received text-slate-100 text-xs p-4 rounded-2xl max-w-[85%] whitespace-pre-line shadow-sm border border-slate-800/60 leading-relaxed font-sans">
                        ${formatWhatsAppMarkdown(text)}
                    </div>
                `;
            }

            chatStream.appendChild(row);
            chatStream.scrollTop = chatStream.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        function formatWhatsAppMarkdown(text) {
            let escaped = escapeHtml(text);
            // Replace *bold* with <strong>
            escaped = escaped.replace(/\*(.*?)\*/g, '<strong class="text-white">$1</strong>');
            // Replace _italic_ with <em>
            escaped = escaped.replace(/_(.*?)_/g, '<em class="text-slate-300">$1</em>');
            return escaped;
        }

        async function handleSendMessage(e) {
            e.preventDefault();
            const msg = chatInput.value.trim();
            const phone = phoneInput.value.trim();

            if (!msg || !phone) return;

            appendMessage(msg, true);
            chatInput.value = '';

            try {
                const res = await fetch("{{ route('play.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: msg, phone: phone })
                });
                const data = await res.json();

                if (data.reply) {
                    appendMessage(data.reply, false);
                }
                if (data.balance !== undefined) {
                    walletHeader.innerText = '{{ $tenant->currency }} ' + Number(data.balance).toLocaleString('en-US', {minimumFractionDigits: 2});
                }
                if (data.recentTickets && data.recentTickets.length > 0) {
                    updateReceiptsList(data.recentTickets);
                }
            } catch (err) {
                appendMessage("⚠️ Error communicating with server. Please try again.", false);
            }
        }

        function sendQuickMessage(text) {
            chatInput.value = text;
            handleSendMessage(new Event('submit'));
        }

        async function handleRegistration(e) {
            e.preventDefault();
            const name = document.getElementById('regName').value.trim();
            const phone = document.getElementById('regPhone').value.trim();
            const pin = document.getElementById('regPin').value.trim();
            const alertBox = document.getElementById('regAlert');

            try {
                const res = await fetch("{{ route('play.register') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name, phone, pin })
                });
                const data = await res.json();

                if (data.success) {
                    alertBox.className = "mt-3 p-3 rounded-xl text-xs bg-emerald-500/10 border border-emerald-500/30 text-emerald-400";
                    alertBox.innerText = data.message;
                    alertBox.classList.remove('hidden');
                    walletHeader.innerText = '{{ $tenant->currency }} ' + Number(data.player.balance).toLocaleString('en-US', {minimumFractionDigits: 2});

                    appendMessage(`🎉 Welcome, *${name}*! Your account is active with trial balance. Type *1* to start playing!`, false);
                }
            } catch (err) {
                alertBox.className = "mt-3 p-3 rounded-xl text-xs bg-rose-500/10 border border-rose-500/30 text-rose-400";
                alertBox.innerText = "Registration failed. Check parameters.";
                alertBox.classList.remove('hidden');
            }
        }

        async function fundAmount(amount) {
            const phone = phoneInput.value.trim();
            try {
                const res = await fetch("{{ route('play.fund') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ phone, amount })
                });
                const data = await res.json();
                if (data.success) {
                    walletHeader.innerText = '{{ $tenant->currency }} ' + Number(data.balance).toLocaleString('en-US', {minimumFractionDigits: 2});
                    appendMessage(`💳 *Dedicated Virtual Account Deposit Confirmed!*\nCredited: *₦${amount.toLocaleString()}*\nNew Balance: *₦${Number(data.balance).toLocaleString()}*`, false);
                }
            } catch (err) {
                alert("Funding failed.");
            }
        }

        function updateReceiptsList(tickets) {
            const container = document.getElementById('receiptsList');
            container.innerHTML = tickets.map(t => `
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800/80 text-xs">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-mono font-bold text-white">${t.ticket_reference}</span>
                        <span class="text-emerald-400 font-bold">{{ $tenant->currency }} ${Number(t.stake_amount).toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400">
                        <span>Game: <strong class="text-slate-300">${t.game_code}</strong></span>
                        <span class="font-mono text-[10px] text-slate-500">${(t.hash || '').substring(0, 10)}...</span>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>

