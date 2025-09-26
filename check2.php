<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pi Network Wallet Explorer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen text-white">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8 animate-fade-in">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-pink-400">
                <i class="fas fa-coins mr-3"></i>Pi Network Wallet Explorer
            </h1>
            <p class="text-xl text-gray-300">Explore wallets, transactions, and blocks with voice search</p>
        </div>

        <!-- Input Section -->
        <div class="max-w-md mx-auto mb-8">
            <div class="relative">
                <input type="text" id="address" placeholder="Enter Pi Wallet Address (e.g., GABC...)" 
                       class="w-full px-4 py-3 pl-12 pr-12 text-gray-900 rounded-xl border-2 border-purple-500/30 bg-white/10 backdrop-blur-md focus:border-purple-400 focus:outline-none transition-all duration-300 text-lg placeholder-gray-400">
                <i class="fas fa-wallet absolute left-4 top-1/2 transform -translate-y-1/2 text-purple-400"></i>
            </div>
            <div class="relative mt-4">
                <textarea id="voiceTranscript" readonly placeholder="Voice transcript will appear here..." 
                          class="w-full px-4 py-3 text-gray-200 rounded-xl border-2 border-blue-500/30 bg-white/10 backdrop-blur-md focus:border-blue-400 focus:outline-none transition-all duration-300 text-lg placeholder-gray-400 resize-none h-24"></textarea>
                <i class="fas fa-microphone absolute left-4 top-4 text-blue-400"></i>
            </div>
            <div class="flex flex-wrap gap-3 mt-4 justify-center">
                <button id="checkBtn" onclick="checkBalance()" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-w-[120px]">
                    <i class="fas fa-search mr-2"></i>Check
                </button>
                <button id="voiceBtn" onclick="startVoiceSearch()" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-w-[120px]">
                    <i class="fas fa-microphone mr-2"></i>Voice
                </button>
                <button id="clearBtn" onclick="clearInput()" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 rounded-xl font-semibold text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-w-[120px]">
                    <i class="fas fa-times mr-2"></i>Clear
                </button>
            </div>
        </div>

        <!-- Result Section -->
        <div id="result" class="max-w-4xl mx-auto hidden animate-slide-up"></div>

        <!-- Transaction/Block Section -->
        <div id="hugeTx" class="max-w-4xl mx-auto mt-12 p-6 bg-white/10 backdrop-blur-md rounded-2xl border border-purple-500/20 animate-slide-up">
            <h2 id="txTitle" class="text-2xl font-bold mb-4 text-center bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 to-orange-400">
                <i class="fas fa-exchange-alt mr-2"></i>Last Huge Transaction on Pi Blockchain
            </h2>
            <div id="txContent" class="text-center py-8">
                <p class="text-gray-300 mb-4">Fetching latest data...</p>
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-400 mx-auto"></div>
            </div>
        </div>
    </div>

    <script>
        const HORIZON_URL = 'https://api.mainnet.minepi.com';
        const PI_ASSET_CODE = 'PI';
        const PI_ISSUER = 'GCZLWPLQH22MJKEUQVRGQQ46D52YLQKMM2FCUV4RTOCBCVY5BFJ35EO4';

        // Function to format balance
        function formatBalance(balance) {
            const num = parseFloat(balance);
            if (isNaN(num)) return '0';
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'k';
            } else {
                return num.toFixed(2);
            }
        }

        // Function to convert base64 to hex
        function base64ToHex(base64) {
            let binary = atob(base64);
            let hex = '';
            for (let i = 0; i < binary.length; i++) {
                let h = binary.charCodeAt(i).toString(16);
                hex += (h.length === 2 ? h : '0' + h);
            }
            return hex.toUpperCase();
        }

        // Function to copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Address copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        async function checkBalance() {
            const address = document.getElementById('address').value.trim();
            const resultDiv = document.getElementById('result');

            if (!address || !address.startsWith('G')) {
                resultDiv.innerHTML = '<p class="text-red-400 text-center p-4">Please enter a valid Pi wallet address starting with "G".</p>';
                resultDiv.classList.remove('hidden');
                return;
            }

            resultDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-purple-400 mb-2"></i><p class="text-gray-300">Loading...</p></div>';
            resultDiv.classList.remove('hidden');

            try {
                const response = await fetch(`${HORIZON_URL}/accounts/${address}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const account = await response.json();

                // Find PI balance (fall back to native if no specific PI asset)
                let piBalance = '0';
                if (account.balances) {
                    const piBalanceObj = account.balances.find(b => 
                        (!b.asset_code || b.asset_code === PI_ASSET_CODE) && 
                        (!b.asset_issuer || b.asset_issuer === PI_ISSUER)
                    );
                    if (piBalanceObj) {
                        piBalance = piBalanceObj.balance;
                    }
                }

                // Find native balance
                const nativeBalance = account.balances.find(b => !b.asset_code)?.balance || '0';

                // Format balances
                const piBalanceRaw = parseFloat(piBalance).toFixed(2);
                const piBalanceFormatted = formatBalance(piBalance);
                const nativeBalanceRaw = parseFloat(nativeBalance).toFixed(2);
                const nativeBalanceFormatted = formatBalance(nativeBalance);

                resultDiv.innerHTML = `
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Pi Balance Card -->
                        <div class="bg-gradient-to-br from-purple-600/20 to-blue-600/20 backdrop-blur-md rounded-2xl p-6 border border-purple-500/30 animate-slide-up">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-coins text-3xl text-purple-400 mr-3"></i>
                                <h2 class="text-2xl font-bold">Pi Balance</h2>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Raw</p>
                                    <p class="text-3xl font-bold text-white">${piBalanceRaw} PI</p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Formatted</p>
                                    <p class="text-3xl font-bold text-white">${piBalanceFormatted} PI</p>
                                </div>
                            </div>
                        </div>

                        <!-- Native Balance Card -->
                        <div class="bg-gradient-to-br from-indigo-600/20 to-cyan-600/20 backdrop-blur-md rounded-2xl p-6 border border-blue-500/30 animate-slide-up">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-globe text-3xl text-blue-400 mr-3"></i>
                                <h2 class="text-2xl font-bold">Native Balance</h2>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Raw</p>
                                    <p class="text-3xl font-bold text-white">${nativeBalanceRaw} (XLM-like)</p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Formatted</p>
                                    <p class="text-3xl font-bold text-white">${nativeBalanceFormatted} (XLM-like)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i><p class="text-red-300">${error.message}. The address may not exist or there may be a network issue.</p></div>`;
            }
        }

        async function fetchWalletDetails() {
            const address = document.getElementById('address').value.trim();
            const resultDiv = document.getElementById('result');

            if (!address || !address.startsWith('G')) {
                resultDiv.innerHTML = '<p class="text-red-400 text-center p-4">Please enter a valid Pi wallet address starting with "G".</p>';
                resultDiv.classList.remove('hidden');
                return;
            }

            resultDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-purple-400 mb-2"></i><p class="text-gray-300">Loading wallet details...</p></div>';
            resultDiv.classList.remove('hidden');

            try {
                const response = await fetch(`${HORIZON_URL}/accounts/${address}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const account = await response.json();

                // Extract wallet details
                const sequence = account.sequence;
                const lastModifiedLedger = account.last_modified_ledger;
                const subentryCount = account.subentry_count;
                const lastModifiedTime = account.last_modified_time ? new Date(account.last_modified_time).toLocaleString() : 'Unknown';

                resultDiv.innerHTML = `
                    <div class="bg-gradient-to-br from-teal-600/20 to-blue-600/20 backdrop-blur-md rounded-2xl p-6 border border-teal-500/30 animate-slide-up">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-wallet text-3xl text-teal-400 mr-3"></i>
                            <h2 class="text-2xl font-bold">Wallet Details</h2>
                        </div>
                        <div class="space-y-2">
                            <div class="bg-white/10 rounded-lg p-4">
                                <p class="text-sm text-gray-300">Address</p>
                                <p class="text-lg font-mono text-white">${address.substring(0, 5)}...${address.slice(-5)}</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4">
                                <p class="text-sm text-gray-300">Sequence Number</p>
                                <p class="text-lg font-bold text-white">${sequence}</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4">
                                <p class="text-sm text-gray-300">Last Modified Ledger</p>
                                <p class="text-lg font-bold text-white">${lastModifiedLedger}</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4">
                                <p class="text-sm text-gray-300">Subentry Count</p>
                                <p class="text-lg font-bold text-white">${subentryCount}</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4">
                                <p class="text-sm text-gray-300">Last Modified Time</p>
                                <p class="text-lg font-bold text-white">${lastModifiedTime}</p>
                            </div>
                        </div>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i><p class="text-red-300">${error.message}. The address may not exist or there may be a network issue.</p></div>`;
            }
        }

        async function fetchLowestTransaction() {
            const txTitle = document.getElementById('txTitle');
            const txContent = document.getElementById('txContent');
            txTitle.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Lowest Recent Transaction on Pi Blockchain';
            txContent.innerHTML = '<p class="text-gray-300 mb-4">Fetching latest data...</p><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-400 mx-auto"></div>';

            try {
                const response = await fetch(`${HORIZON_URL}/payments?order=desc&limit=50`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();

                let minAmount = Infinity;
                let lowestPayment = null;
                data._embedded.records.forEach(payment => {
                    if (payment.asset_type === 'native') {  // Treat native as PI
                        const amount = parseFloat(payment.amount);
                        if (amount < minAmount) {
                            minAmount = amount;
                            lowestPayment = payment;
                        }
                    }
                });

                if (lowestPayment) {
                    const timestamp = new Date(lowestPayment.created_at).toLocaleString();
                    const amount = lowestPayment.amount;
                    const formattedAmount = formatBalance(amount);
                    const fromShort = lowestPayment.from.substring(0, 5) + '...' + lowestPayment.from.slice(-5);
                    const toShort = lowestPayment.to.substring(0, 5) + '...' + lowestPayment.to.slice(-5);
                    const fromFull = lowestPayment.from;
                    const toFull = lowestPayment.to;
                    const hashBase64 = lowestPayment.transaction_hash;
                    const hashHex = base64ToHex(hashBase64);

                    txContent.innerHTML = `
                        <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 rounded-xl p-6 border-l-4 border-yellow-400">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-gray-300">${timestamp}</span>
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-xs font-semibold">PI Payment</span>
                            </div>
                            <h3 class="text-3xl font-bold mb-2 text-white">+${formattedAmount} PI</h3>
                            <p class="text-gray-400 mb-2">
                                <i class="fas fa-arrow-right mr-1"></i>From: <span class="font-mono text-purple-300">${fromShort}</span>
                                <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${fromFull}')"></i>
                            </p>
                            <p class="text-gray-400">
                                <i class="fas fa-arrow-left mr-1"></i>To: <span class="font-mono text-blue-300">${toShort}</span>
                                <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${toFull}')"></i>
                            </p>
                            <a href="${HORIZON_URL}/transactions/${hashHex}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white text-sm font-semibold transition-colors">
                                <i class="fas fa-external-link-alt mr-1"></i>View on Explorer
                            </a>
                        </div>
                    `;
                } else {
                    txContent.innerHTML = '<p class="text-red-400 text-center">No recent PI transactions found.</p>';
                }
            } catch (error) {
                txContent.innerHTML = `<p class="text-red-400 text-center">Error: ${error.message}</p>`;
            }
        }

        async function fetchLowestPiWallet() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-purple-400 mb-2"></i><p class="text-gray-300">Loading lowest PI wallet...</p></div>';
            resultDiv.classList.remove('hidden');

            try {
                const response = await fetch(`${HORIZON_URL}/accounts?order=desc&limit=100`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();

                let minBalance = Infinity;
                let lowestAccount = null;
                data._embedded.records.forEach(account => {
                    let piBalance = '0';
                    if (account.balances) {
                        const piBalanceObj = account.balances.find(b => 
                            (!b.asset_code || b.asset_code === PI_ASSET_CODE) && 
                            (!b.asset_issuer || b.asset_issuer === PI_ISSUER)
                        );
                        if (piBalanceObj) {
                            piBalance = piBalanceObj.balance;
                        }
                    }
                    const balanceNum = parseFloat(piBalance);
                    if (balanceNum < minBalance) {
                        minBalance = balanceNum;
                        lowestAccount = account;
                    }
                });

                if (lowestAccount) {
                    const piBalance = lowestAccount.balances.find(b => 
                        (!b.asset_code || b.asset_code === PI_ASSET_CODE) && 
                        (!b.asset_issuer || b.asset_issuer === PI_ISSUER)
                    )?.balance || '0';
                    const piBalanceRaw = parseFloat(piBalance).toFixed(2);
                    const piBalanceFormatted = formatBalance(piBalance);
                    const address = lowestAccount.account_id;
                    const addressShort = address.substring(0, 5) + '...' + address.slice(-5);

                    resultDiv.innerHTML = `
                        <div class="bg-gradient-to-br from-teal-600/20 to-blue-600/20 backdrop-blur-md rounded-2xl p-6 border border-teal-500/30 animate-slide-up">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-wallet text-3xl text-teal-400 mr-3"></i>
                                <h2 class="text-2xl font-bold">Lowest PI Wallet</h2>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Address</p>
                                    <p class="text-lg font-mono text-white">${addressShort} <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${address}')"></i></p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">PI Balance (Raw)</p>
                                    <p class="text-lg font-bold text-white">${piBalanceRaw} PI</p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">PI Balance (Formatted)</p>
                                    <p class="text-lg font-bold text-white">${piBalanceFormatted} PI</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<p class="text-red-400 text-center">No PI wallets found in sample.</p>';
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i><p class="text-red-300">${error.message}. Unable to fetch accounts.</p></div>`;
            }
        }

        async function fetchHugePiWallet() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-purple-400 mb-2"></i><p class="text-gray-300">Loading highest PI wallet...</p></div>';
            resultDiv.classList.remove('hidden');

            try {
                const response = await fetch(`${HORIZON_URL}/accounts?order=desc&limit=100`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();

                let maxBalance = 0;
                let highestAccount = null;
                data._embedded.records.forEach(account => {
                    let piBalance = '0';
                    if (account.balances) {
                        const piBalanceObj = account.balances.find(b => 
                            (!b.asset_code || b.asset_code === PI_ASSET_CODE) && 
                            (!b.asset_issuer || b.asset_issuer === PI_ISSUER)
                        );
                        if (piBalanceObj) {
                            piBalance = piBalanceObj.balance;
                        }
                    }
                    const balanceNum = parseFloat(piBalance);
                    if (balanceNum > maxBalance) {
                        maxBalance = balanceNum;
                        highestAccount = account;
                    }
                });

                if (highestAccount) {
                    const piBalance = highestAccount.balances.find(b => 
                        (!b.asset_code || b.asset_code === PI_ASSET_CODE) && 
                        (!b.asset_issuer || b.asset_issuer === PI_ISSUER)
                    )?.balance || '0';
                    const piBalanceRaw = parseFloat(piBalance).toFixed(2);
                    const piBalanceFormatted = formatBalance(piBalance);
                    const address = highestAccount.account_id;
                    const addressShort = address.substring(0, 5) + '...' + address.slice(-5);

                    resultDiv.innerHTML = `
                        <div class="bg-gradient-to-br from-teal-600/20 to-blue-600/20 backdrop-blur-md rounded-2xl p-6 border border-teal-500/30 animate-slide-up">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-wallet text-3xl text-teal-400 mr-3"></i>
                                <h2 class="text-2xl font-bold">Highest PI Wallet</h2>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">Address</p>
                                    <p class="text-lg font-mono text-white">${addressShort} <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${address}')"></i></p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">PI Balance (Raw)</p>
                                    <p class="text-lg font-bold text-white">${piBalanceRaw} PI</p>
                                </div>
                                <div class="bg-white/10 rounded-lg p-4">
                                    <p class="text-sm text-gray-300">PI Balance (Formatted)</p>
                                    <p class="text-lg font-bold text-white">${piBalanceFormatted} PI</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<p class="text-red-400 text-center">No PI wallets found in sample.</p>';
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i><p class="text-red-300">${error.message}. Unable to fetch accounts.</p></div>`;
            }
        }

        async function fetchLatestBlock() {
            const txTitle = document.getElementById('txTitle');
            const txContent = document.getElementById('txContent');
            txTitle.innerHTML = '<i class="fas fa-cube mr-2"></i>Latest Block on Pi Blockchain';
            txContent.innerHTML = '<p class="text-gray-300 mb-4">Fetching latest block...</p><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-400 mx-auto"></div>';

            try {
                const response = await fetch(`${HORIZON_URL}/ledgers?order=desc&limit=1`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                const ledger = data._embedded.records[0];

                if (ledger) {
                    const sequence = ledger.sequence;
                    const timestamp = new Date(ledger.closed_at).toLocaleString();
                    const hash = ledger.hash.substring(0, 5) + '...' + ledger.hash.slice(-5);
                    const transactionCount = ledger.successful_transaction_count || 0;

                    txContent.innerHTML = `
                        <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 rounded-xl p-6 border-l-4 border-yellow-400">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-gray-300">${timestamp}</span>
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-xs font-semibold">Ledger</span>
                            </div>
                            <h3 class="text-3xl font-bold mb-2 text-white">Ledger #${sequence}</h3>
                            <p class="text-gray-400 mb-2"><i class="fas fa-cube mr-1"></i>Hash: <span class="font-mono text-purple-300">${hash}</span></p>
                            <p class="text-gray-400"><i class="fas fa-exchange-alt mr-1"></i>Transactions: <span class="font-bold text-white">${transactionCount}</span></p>
                            <a href="${HORIZON_URL}/ledgers/${sequence}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white text-sm font-semibold transition-colors">
                                <i class="fas fa-external-link-alt mr-1"></i>View on Explorer
                            </a>
                        </div>
                    `;
                } else {
                    txContent.innerHTML = '<p class="text-red-400 text-center">No recent ledger found.</p>';
                }
            } catch (error) {
                txContent.innerHTML = `<p class="text-red-400 text-center">Error: ${error.message}</p>`;
            }
        }

        async function fetchLastTransaction() {
            const txTitle = document.getElementById('txTitle');
            const txContent = document.getElementById('txContent');
            txTitle.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Last Transaction on Pi Blockchain';
            txContent.innerHTML = '<p class="text-gray-300 mb-4">Fetching latest data...</p><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-400 mx-auto"></div>';

            try {
                const response = await fetch(`${HORIZON_URL}/payments?order=desc&limit=1`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                const payment = data._embedded.records[0];

                if (payment && payment.asset_type === 'native') {  // Treat native as PI
                    const timestamp = new Date(payment.created_at).toLocaleString();
                    const amount = payment.amount;
                    const formattedAmount = formatBalance(amount);
                    const fromShort = payment.from.substring(0, 5) + '...' + payment.from.slice(-5);
                    const toShort = payment.to.substring(0, 5) + '...' + payment.to.slice(-5);
                    const fromFull = payment.from;
                    const toFull = payment.to;
                    const hashBase64 = payment.transaction_hash;
                    const hashHex = base64ToHex(hashBase64);

                    txContent.innerHTML = `
                        <div class="bg-gradient-to-r from-green-600/20 to-blue-600/20 rounded-xl p-6 border-l-4 border-yellow-400">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-gray-300">${timestamp}</span>
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-xs font-semibold">PI Payment</span>
                            </div>
                            <h3 class="text-3xl font-bold mb-2 text-white">+${formattedAmount} PI</h3>
                            <p class="text-gray-400 mb-2">
                                <i class="fas fa-arrow-right mr-1"></i>From: <span class="font-mono text-purple-300">${fromShort}</span>
                                <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${fromFull}')"></i>
                            </p>
                            <p class="text-gray-400">
                                <i class="fas fa-arrow-left mr-1"></i>To: <span class="font-mono text-blue-300">${toShort}</span>
                                <i class="fas fa-copy ml-2 cursor-pointer text-gray-300 hover:text-white" onclick="copyToClipboard('${toFull}')"></i>
                            </p>
                            <a href="${HORIZON_URL}/transactions/${hashHex}" target="_blank" class="mt-4 inline-block px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-white text-sm font-semibold transition-colors">
                                <i class="fas fa-external-link-alt mr-1"></i>View on Explorer
                            </a>
                        </div>
                    `;
                } else {
                    txContent.innerHTML = '<p class="text-red-400 text-center">No recent PI transaction found.</p>';
                }
            } catch (error) {
                txContent.innerHTML = `<p class="text-red-400 text-center">Error: ${error.message}</p>`;
            }
        }

        function clearInput() {
            document.getElementById('address').value = '';
            document.getElementById('voiceTranscript').value = '';
            document.getElementById('result').innerHTML = '';
            document.getElementById('result').classList.add('hidden');
        }

        // Voice Search Setup
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            const recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.lang = 'en-US';
            recognition.interimResults = true; // Enable interim results for real-time transcription

            recognition.onresult = (event) => {
                const transcriptDiv = document.getElementById('voiceTranscript');
                let finalTranscript = '';
                let interimTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript;
                    } else {
                        interimTranscript += event.results[i][0].transcript;
                    }
                }

                transcriptDiv.value = finalTranscript + interimTranscript;

                if (event.results[event.results.length - 1].isFinal) {
                    processVoiceQuery(finalTranscript);
                }
            };

            recognition.onspeechend = () => {
                recognition.stop();
            };

            recognition.onerror = (event) => {
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = `<p class="text-red-400 text-center p-4">Speech recognition error: ${event.error}</p>`;
                resultDiv.classList.remove('hidden');
            };

            window.startVoiceSearch = () => {
                document.getElementById('voiceTranscript').value = ''; // Clear previous transcript
                recognition.start();
            };
        } else {
            window.startVoiceSearch = () => {
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = '<p class="text-red-400 text-center p-4">Speech recognition not supported in this browser. Please use Chrome or a supported browser.</p>';
                resultDiv.classList.remove('hidden');
            };
        }

        function processVoiceQuery(transcript) {
            const lower = transcript.toLowerCase().trim();
            const resultDiv = document.getElementById('result');
            resultDiv.classList.remove('hidden');

            if (lower.includes('balance') || lower.includes('check')) {
                checkBalance();
            } else if (lower.includes('last huge transaction') || lower.includes('huge transaction')) {
                loadHugeTx();
            } else if (lower.includes('lowest transaction')) {
                fetchLowestTransaction();
            } else if (lower.includes('lowest pi wallet') || lower.includes('lowest wallet')) {
                fetchLowestPiWallet();
            } else if (lower.includes('huge pi wallet') || lower.includes('highest wallet')) {
                fetchHugePiWallet();
            } else if (lower.includes('latest block') || lower.includes('last block')) {
                fetchLatestBlock();
            } else if (lower.includes('last transaction')) {
                fetchLastTransaction();
            } else if (lower.includes('wallet details') || lower.includes('account details')) {
                fetchWalletDetails();
            } else if (lower.includes('search') || lower.includes('explore')) {
                resultDiv.innerHTML = `<p class="text-yellow-400 text-center p-4">Searching Pi Blockchain for "${transcript}". (Feature under development - showing last transaction as example.)</p>`;
                fetchLastTransaction(); // Placeholder: fetch last tx as example
            } else {
                resultDiv.innerHTML = `<p class="text-yellow-400 text-center p-4">Sorry, I didn't understand: "${transcript}". Try saying "check balance", "last transaction", "lowest transaction", "last huge transaction", "lowest pi wallet", "huge pi wallet", "latest block", "wallet details", "search [query]", or "clear".</p>`;
            }
        }

        // Load huge transaction on page load
        loadHugeTx();
    </script>
</body>
</html>