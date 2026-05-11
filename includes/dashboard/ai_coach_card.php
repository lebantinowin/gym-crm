<?php
// includes/dashboard/ai_coach_card.php - API-First Version
?>
<div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 group">
    <div class="p-6 bg-primary flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="bg-highlight p-3 rounded-2xl shadow-lg">
                <i class="fas fa-robot text-white"></i>
            </div>
            <div>
                <h3 class="text-xl font-black text-white">AI Coach</h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Intelligence</p>
            </div>
        </div>
        <a href="chat.php" class="text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="p-8">
        <div id="insightSkeleton" class="space-y-4">
            <div class="h-20 bg-gray-50 rounded-2xl animate-pulse"></div>
            <div class="h-4 bg-gray-50 rounded-full w-2/3 animate-pulse"></div>
        </div>
        
        <div id="insightContent" class="hidden">
            <div class="bg-gray-50 rounded-2xl p-6 mb-8 relative border border-gray-100">
                <i class="fas fa-quote-left absolute -top-3 -left-2 text-highlight opacity-20 text-4xl"></i>
                <p id="coachMessage" class="italic text-gray-700 leading-relaxed font-medium">Analyzing your latest performance...</p>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="font-bold text-gray-400 uppercase tracking-widest">Coach Confidence</span>
                    <span id="confidenceValue" class="font-black text-highlight">--%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 shadow-inner">
                    <div id="confidenceBar" class="bg-highlight h-2 rounded-full transition-all duration-1000 shadow-lg shadow-highlight/30" style="width: 0%"></div>
                </div>
            </div>
            
            <a href="chat.php" class="block w-full text-center bg-gray-900 text-white py-4 rounded-2xl mt-8 font-black text-sm tracking-widest hover:bg-black transition shadow-lg">
                ENTER COMMAND CENTER
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const content = document.getElementById('insightContent');
    const skeleton = document.getElementById('insightSkeleton');
    
    try {
        const response = await fetch('api/coach_insight.php');
        const res = await response.json();
        
        if (res.status === 'success') {
            document.getElementById('coachMessage').innerText = `"${res.data.insight}"`;
            document.getElementById('confidenceValue').innerText = `${res.data.confidence}%`;
            
            skeleton.classList.add('hidden');
            content.classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('confidenceBar').style.width = `${res.data.confidence}%`;
            }, 100);
        }
    } catch (err) { console.error('Coach insight error:', err); }
});
</script>
