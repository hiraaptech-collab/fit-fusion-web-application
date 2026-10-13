require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json({ limit: '10kb' }));
app.use(express.static(path.join(__dirname)));

const SYSTEM_PROMPT = `You are Fusion-AI, a premium AI fitness assistant created by FitFusion. You are an expert in fitness, gym workouts, exercise, bodybuilding, weight training, cardio, BMI, body composition, general nutrition, healthy lifestyle, fitness routines, workout planning, recovery, stretching, gym equipment, exercise techniques, and general fitness education.

IMPORTANT RULES:
1. ONLY answer questions related to fitness, health, gym, workouts, nutrition, bodybuilding, exercise, BMI, body composition, recovery, stretching, and healthy lifestyle.
2. If the user asks about something unrelated to fitness/health, politely redirect them: "I'm Fusion-AI, your dedicated fitness assistant. I can help you with workouts, nutrition, BMI, exercise techniques, and wellness topics. How can I assist you with your fitness journey today?"
3. Do NOT diagnose diseases, prescribe medication, or pretend to be a doctor.
4. For serious medical conditions, injuries, or emergencies, always recommend: "For serious health concerns, please consult a qualified healthcare professional."
5. Be encouraging, motivational, and professional.
6. You can respond in any language the user writes in (English, Urdu, Roman Urdu, Hindi, Arabic, etc.).
7. Keep responses helpful and concise. Use formatting for readability.
8. Include the disclaimer when giving health/nutrition advice: "This is general wellness information and not a substitute for professional medical advice."`;

function normalizeWhatsAppNumber(value) {
    if (!value) return '';
    return value.replace(/[^0-9]/g, '');
}

function generateLocalResponse(lastUserMessage) {
    const q = lastUserMessage.toLowerCase();

    const greetings = /^(hi|hello|hey|assalam|salam|howdy|sup|yo)\b/i;
    if (greetings.test(q)) {
        return "Hello! I'm Fusion-AI, your fitness assistant. I can help you with workouts, nutrition, BMI, exercise tips, and more. What would you like to know?";
    }

    const exit = /^(bye|goodbye|thanks|thank you|ok|okay)\b/i;
    if (exit.test(q)) {
        return "You're welcome! Stay consistent with your fitness goals. Feel free to come back anytime you need help. 💪";
    }

    const workouts = [
        { keys: ['chest', 'pec'], r: "For chest, try these:\n\n• **Barbell Bench Press** — 4x8\n• **Incline Dumbbell Press** — 3x10\n• **Cable Flyes** — 3x12\n• **Push-ups** — 3x to failure\n\nRest 60–90 seconds between sets. Keep your shoulder blades retracted and feet flat on the floor." },
        { keys: ['back', 'lat', 'pull'], r: "For back development:\n\n• **Deadlifts** — 4x6\n• **Pull-ups / Lat Pulldown** — 4x8\n• **Barbell Rows** — 3x10\n• **Seated Cable Row** — 3x12\n\nFocus on squeezing your shoulder blades together at the top of each rep." },
        { keys: ['leg', 'squat', 'thigh', 'quad', 'hamstring'], r: "For legs:\n\n• **Barbell Squat** — 4x8\n• **Romanian Deadlift** — 3x10\n• **Leg Press** — 3x12\n• **Walking Lunges** — 3x12 each\n• **Calf Raises** — 4x15\n\nLeg day is crucial for overall strength and growth hormone production. Don't skip it!" },
        { keys: ['shoulder', 'delt'], r: "For shoulders:\n\n• **Overhead Press** — 4x8\n• **Lateral Raises** — 3x12\n• **Face Pulls** — 3x15\n• **Rear Delt Flyes** — 3x12\n\nStrong shoulders protect your joints and improve your physique." },
        { keys: ['arm', 'bicep', 'tricep'], r: "For arms:\n\n• **Barbell Curl** — 3x10\n• **Hammer Curls** — 3x12\n• **Tricep Dips** — 3x10\n• **Overhead Tricep Extension** — 3x12\n\nArms respond well to higher reps (12–15) and controlled tempo." },
        { keys: ['abs', 'core', 'stomach', 'belly'], r: "For core strength:\n\n• **Plank** — 3x 45–60 sec\n• **Hanging Leg Raises** — 3x12\n• **Cable Crunches** — 3x15\n• **Dead Bugs** — 3x10 each side\n\nA strong core improves posture, reduces injury risk, and boosts performance in all lifts." },
        { keys: ['full body', 'full-body', 'fullbody'], r: "Full-body workout (3 days/week):\n\n**Day A:** Squat 4x5, Bench Press 4x5, Barbell Row 4x5\n**Day B:** Deadlift 3x5, Overhead Press 3x5, Pull-ups 3x max\n\nAlternate A/B with rest days between. This is a classic strength program." },
    ];
    for (const w of workouts) {
        if (w.keys.some(k => q.includes(k))) return w.r;
    }

    const nutrition = [
        { keys: ['protein', 'eat for protein'], r: "**Top protein sources:**\n\n• Chicken breast — 31g per 100g\n• Eggs — 6g per egg\n• Greek yogurt — 10g per 100g\n• Lentils — 9g per 100g cooked\n• Tuna — 26g per 100g\n• Paneer — 18g per 100g\n• Whey protein — 24g per scoop\n\nAim for 1.6–2.2g of protein per kg of body weight for muscle building." },
        { keys: ['diet', 'meal', 'food', 'eat'], r: "**Balanced diet principles:**\n\n• Protein: 25–30% of calories (chicken, fish, eggs, legumes)\n• Carbs: 40–50% (rice, oats, sweet potato, fruits)\n• Fats: 20–30% (nuts, olive oil, avocado)\n\nEat whole foods, stay hydrated (3–4L water/day), and meal prep to stay consistent." },
        { keys: ['weight loss', 'fat loss', 'lose weight', 'burn fat'], r: "**Sustainable fat loss approach:**\n\n1. Caloric deficit: eat 300–500 calories below maintenance\n2. Protein high: 2g per kg to preserve muscle\n3. Strength train 3–4x/week\n4. Walk 8,000–10,000 steps daily\n5. Sleep 7–8 hours\n\nAvoid crash diets. Aim to lose 0.5–1% of body weight per week." },
        { keys: ['weight gain', 'bulk', 'gain weight', 'muscle gain'], r: "**Clean muscle gain:**\n\n1. Caloric surplus: eat 300–500 calories above maintenance\n2. Protein: 1.8–2.2g per kg body weight\n3. Progressive overload in the gym\n4. Sleep 7–9 hours (growth hormone peaks during deep sleep)\n5. Be patient — aim for 0.25–0.5 kg per week\n\nThis is general wellness information and not a substitute for professional medical advice." },
        { keys: ['supplement', 'creatine', 'whey', 'pre-workout'], r: "**Evidence-based supplements:**\n\n• **Creatine monohydrate** — 5g/day, improves strength & muscle\n• **Whey protein** — convenient protein source\n• **Vitamin D** — if deficient (common in South Asia)\n• **Fish oil** — anti-inflammatory, heart health\n• **Caffeine** — 3–6mg/kg pre-workout for performance\n\nMost supplements are optional. Food first, supplements second." },
    ];
    for (const n of nutrition) {
        if (n.keys.some(k => q.includes(k))) return n.r;
    }

    const bmi = [
        { keys: ['bmi', 'body mass index'], r: "**BMI Categories:**\n\n• Underweight: below 18.5\n• Normal: 18.5 – 24.9\n• Overweight: 25 – 29.9\n• Obese: 30+\n\n**How to calculate:** BMI = weight(kg) / height(m)²\n\nNote: BMI doesn't account for muscle mass. A muscular person may have a high BMI but low body fat. Use it as a starting point, not a final diagnosis." },
    ];
    for (const b of bmi) {
        if (b.keys.some(k => q.includes(k))) return b.r;
    }

    const cardio = [
        { keys: ['cardio', 'running', 'jog', 'walk', 'hiit'], r: "**Cardio recommendations:**\n\n• **Beginners:** 3x/week, 20–30 min brisk walking or light jog\n• **Intermediate:** 3x/week, 20 min HIIT (intervals of 30s sprint / 60s walk)\n• **Advanced:** 4x/week mixing steady-state and HIIT\n\nCardio improves heart health, endurance, and recovery. Balance it with strength training." },
    ];
    for (const c of cardio) {
        if (c.keys.some(k => q.includes(k))) return c.r;
    }

    const recovery = [
        { keys: ['rest', 'recovery', 'sleep', 'stretch', 'flexibility'], r: "**Recovery essentials:**\n\n• Sleep 7–9 hours (most muscle repair happens during sleep)\n• Stretch 10 min post-workout\n• Take 1–2 rest days per week\n• Foam roll tight muscles\n• Stay hydrated\n\nOvertraining leads to injury and burnout. Recovery is where growth happens." },
    ];
    for (const rec of recovery) {
        if (rec.keys.some(k => q.includes(k))) return rec.r;
    }

    const motivation = [
        { keys: ['motivation', 'consistent', 'stuck', 'give up', 'hard'], r: "**Staying consistent:**\n\n1. Set small, achievable goals (e.g., gym 3x this week)\n2. Track your workouts — progress is motivating\n3. Find a workout partner or community\n4. Remember: discipline > motivation\n5. Every workout counts, even a short one\n\nThe hardest part is starting. Once you're in the gym, you've already won." },
    ];
    for (const m of motivation) {
        if (m.keys.some(k => q.includes(k))) return m.r;
    }

    return "I'm Fusion-AI, your fitness assistant. I can help with workouts, nutrition, BMI, exercise techniques, and wellness topics. For the best experience, please try again when the AI service is available. In the meantime, feel free to ask about specific workouts, diet plans, or fitness tips!";
}

async function callAIProvider(messages) {
    const apiKey = process.env.AI_API_KEY;
    const baseUrl = process.env.AI_BASE_URL || 'https://api.groq.com/openai';
    const model = process.env.AI_MODEL || 'openai/gpt-oss-20b';

    if (!apiKey || apiKey === 'YOUR_GROQ_API_KEY_HERE') {
        throw new Error('API_KEY_NOT_CONFIGURED');
    }

    let response;
    try {
        response = await fetch(`${baseUrl}/v1/chat/completions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify({
                model: model,
                messages: [
                    { role: 'system', content: SYSTEM_PROMPT },
                    ...messages
                ],
                temperature: 0.7,
                max_tokens: 1024,
                stream: false
            })
        });
    } catch (error) {
        console.error('[Fusion-AI] Network error reaching AI provider:', error.message);
        throw new Error('NETWORK_ERROR');
    }

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));

        console.error('[Fusion-AI] AI provider returned an error.');
        console.error('[Fusion-AI] Status:', response.status);
        console.error('[Fusion-AI] Error body:', JSON.stringify(errorData).slice(0, 500));

        switch (response.status) {
            case 400:
            case 422:
                throw new Error('INVALID_REQUEST');
            case 401:
                throw new Error('INVALID_API_KEY');
            case 402:
                throw new Error('QUOTA_EXCEEDED');
            case 403:
                throw new Error('FORBIDDEN');
            case 404:
                throw new Error('NOT_FOUND');
            case 413:
                throw new Error('MESSAGE_TOO_LARGE');
            case 429:
                throw new Error('RATE_LIMITED');
            default:
                if (response.status >= 500) throw new Error('UPSTREAM_ERROR');
                throw new Error(`AI API error: ${response.status}`);
        }
    }

    const data = await response.json();

    if (data.choices && data.choices[0] && data.choices[0].message && typeof data.choices[0].message.content === 'string') {
        const content = data.choices[0].message.content.trim();
        if (content) return content;
    }

    console.error('[Fusion-AI] AI provider returned an empty/unexpected payload:', JSON.stringify(data).slice(0, 300));
    throw new Error('EMPTY_RESPONSE');
}

app.post('/api/chat', async (req, res) => {
    const { messages } = req.body;

    if (!messages || !Array.isArray(messages) || messages.length === 0) {
        return res.status(400).json({ error: 'Messages array is required and cannot be empty.' });
    }

    if (messages.length > 50) {
        return res.status(400).json({ error: 'Too many messages. Please start a new conversation.' });
    }

    const lastMsg = messages[messages.length - 1];
    if (!lastMsg.content || !lastMsg.content.trim()) {
        return res.status(400).json({ error: 'Message cannot be empty.' });
    }

    if (lastMsg.content.length > 1000) {
        return res.status(400).json({ error: 'Message too long. Please keep it under 1000 characters.' });
    }

    try {
        const reply = await callAIProvider(messages);
        res.json({ reply });

    } catch (error) {
        console.error('[Fusion-AI] Chat request failed:', error.message);
        if (error.stack) console.error(error.stack);

        const useFallback = ['QUOTA_EXCEEDED', 'INVALID_API_KEY', 'FORBIDDEN', 'UPSTREAM_ERROR', 'NETWORK_ERROR', 'API_KEY_NOT_CONFIGURED'].includes(error.message);

        if (useFallback) {
            const userMsg = messages[messages.length - 1].content;
            const fallback = generateLocalResponse(userMsg);
            return res.json({ reply: fallback, source: 'local' });
        }

        const errorMap = {
            INVALID_REQUEST: { status: 400, code: 'INVALID_REQUEST', message: 'The request to the AI service was invalid.' },
            MESSAGE_TOO_LARGE: { status: 400, code: 'MESSAGE_TOO_LARGE', message: 'The message is too long for the AI service.' },
            RATE_LIMITED: { status: 429, code: 'RATE_LIMITED', message: 'Too many requests. Please wait a moment and try again.' },
            EMPTY_RESPONSE: { status: 502, code: 'EMPTY_RESPONSE', message: 'The AI service returned an empty response.' },
        };

        const mapped = errorMap[error.message];
        if (mapped) {
            return res.status(mapped.status).json({ error: mapped.message, code: mapped.code });
        }

        const fallback = generateLocalResponse(messages[messages.length - 1].content);
        res.json({ reply: fallback, source: 'local' });
    }
});

app.get('/api/config', (req, res) => {
    const whatsappNumber = normalizeWhatsAppNumber(process.env.WHATSAPP_NUMBER) || '923103701543';
    res.json({ whatsappNumber });
});

app.get('*', (req, res) => {
    const p = req.path;

    if (p.includes('.') || p.includes('..') || p.startsWith('.') || p.includes('/.')) {
        return res.status(404).json({ error: 'Not found', code: 'NOT_FOUND' });
    }

    res.sendFile(path.join(__dirname, 'index.html'));
});

app.listen(PORT, () => {
    const key = process.env.AI_API_KEY;
    const aiConfigured = !!(key && key !== 'YOUR_GROQ_API_KEY_HERE');
    const baseUrl = process.env.AI_BASE_URL || 'https://api.groq.com/openai';
    const model = process.env.AI_MODEL || 'openai/gpt-oss-20b';

    console.log(`\n  FitFusion Server running at http://localhost:${PORT}`);
    console.log(`  Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`  AI provider : ${baseUrl} (model: ${model})`);
    console.log(`  AI key      : ${aiConfigured ? 'CONFIGURED' : 'NOT CONFIGURED'}`);
    console.log(`  WhatsApp    : +${normalizeWhatsAppNumber(process.env.WHATSAPP_NUMBER) || 'None'}\n`);
});
