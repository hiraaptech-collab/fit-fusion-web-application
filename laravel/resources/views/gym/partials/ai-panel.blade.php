<section class="fusion-ai-section" id="fusion-chat" aria-label="Fusion-AI chat interface">
    <div class="container">
        <div class="fusion-ai-container">
            <div class="fusion-ai-header">
                <div class="fusion-ai-header-left">
                    <div class="ai-orb"><i class="bi bi-robot"></i></div>
                    <div class="ai-title-group">
                        <h4>Fusion-AI <span class="ai-status-dot"></span></h4>
                        <span>Online &amp; ready to help</span>
                    </div>
                </div>
                <button class="btn-clear-chat" id="clearChatBtn" type="button" aria-label="Clear chat history">
                    <i class="bi bi-trash3"></i> Clear Chat
                </button>
            </div>

            <div class="fusion-ai-messages" id="chatMessages" aria-live="polite" aria-label="Chat messages">
                <div class="fusion-ai-welcome" id="chatWelcome" aria-label="Welcome message">
                    <div class="welcome-icon"><i class="bi bi-robot"></i></div>
                    <h3>Hi, I'm Fusion-AI!</h3>
                    <p>I can help you with workout plans, diet guidance, BMI questions, and expert wellness tips. What's your fitness goal today?</p>
                    <div class="suggested-prompts">
                        <button class="suggested-prompt" type="button" data-prompt="Create a beginner workout plan for me">
                            <i class="bi bi-activity"></i> Beginner Workout Plan
                        </button>
                        <button class="suggested-prompt" type="button" data-prompt="Give me a chest workout">
                            <i class="bi bi-bezier2"></i> Chest Workout
                        </button>
                        <button class="suggested-prompt" type="button" data-prompt="Explain BMI to me simply">
                            <i class="bi bi-calculator"></i> Explain BMI
                        </button>
                        <button class="suggested-prompt" type="button" data-prompt="Suggest a plan to lose weight healthily">
                            <i class="bi bi-egg-fried"></i> Weight Loss Plan
                        </button>
                    </div>
                </div>
            </div>

            <div class="fusion-ai-input-area">
                <textarea class="fusion-ai-input" id="chatInput" rows="1"
                          placeholder="Ask me anything about fitness, diet, or wellness..."
                          aria-label="Type your message to Fusion-AI"></textarea>
                <button class="btn-send" id="sendBtn" type="button" aria-label="Send message" disabled>
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</section>
