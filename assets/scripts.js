const questions = [
    { question: "¿Reside En Los Estados Unidos?", answers: ["Sí", "No"] },
    { question: "¿Debe Más De $7,000 En Tarjetas De Crédito?", answers: ["Sí", "No"] },
    { question: "Haga Clic Debajo Para Reclamar Su Alivio Financiero", answers: ["🔍 Ver Si Califica Ahora"] }
];

let currentQuestion = 0;
let score = 0;
let campaignValue = "309838"; // Valor inicial de la campaña
let ff_hit_id;

const scoringQuestions = [0, 1]; // Ambas primeras preguntas suman puntos si se responde "Sí"

const validationMessages = [
    { text: "Validando sus respuestas...", icon: "<i class='bi bi-check-circle text-success fs-3'></i>" },
    { text: "Analizando opciones de reducción de deuda...", icon: "<i class='bi bi-search text-primary fs-3'></i>" },
    { text: "Confirmando elegibilidad...", icon: "<i class='bi bi-shield-check text-info fs-3'></i>" }
];

document.addEventListener('DOMContentLoaded', () => {
    const progress = document.getElementById('quiz-progress');
    
    // Проверяем существование элемента progress
    if (progress) {
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        if (isMobile) {
            progress.style.width = '7%';
            progress.textContent = '0%'; 
        } else {
            progress.style.width = '4%';
            progress.textContent = '0%';
        }
    }

    // Проверяем существование элементов для quiz
    const questionElement = document.getElementById('quiz-question');
    const buttonsContainer = document.getElementById('quiz-buttons');
    
    if (questionElement && buttonsContainer) {
        renderQuestion();
    }

    // Iniciar countdown
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        const fiveMinutes = 5 * 60;
        startCountdown(fiveMinutes, countdownElement);
    }
});

function renderQuestion() {
    const questionElement = document.getElementById('quiz-question');
    const buttonsContainer = document.getElementById('quiz-buttons');
    const progress = document.getElementById('quiz-progress');

    let questionText = questions[currentQuestion].question;
    questionElement.innerHTML = `<strong>${questionText}</strong>`;
    buttonsContainer.innerHTML = '';

    questions[currentQuestion].answers.forEach((answer, index) => {
        const button = document.createElement('button');
        
        // Estilo base para todos los botones
        button.style.margin = '12px 0';
        button.style.width = '100%';
        button.style.fontWeight = 'bold';
        button.style.padding = '15px 0';
        button.style.borderRadius = '8px';
        button.style.fontSize = '1.1rem';
        button.style.transition = 'all 0.3s';
        button.style.position = 'relative';

        // Estilo para la tercera pregunta (botón único)
        if (currentQuestion === 2) {
            button.className = 'btn';
            button.style.background = 'linear-gradient(135deg, #28a745, #1e7e34)';
            button.style.color = 'white';
            button.style.border = '2px solid white';
            button.style.outline = '2px solid #28a745';
            button.style.boxShadow = '0 5px 20px rgba(40, 167, 69, 0.4)';
            button.style.fontWeight = '800';
            button.style.animation = 'pulse 1.5s infinite';
            
            button.onmouseenter = () => {
                button.style.transform = 'translateY(-3px)';
                button.style.boxShadow = '0 8px 25px rgba(40, 167, 69, 0.6)';
            };
            button.onmouseleave = () => {
                button.style.transform = 'translateY(0)';
                button.style.boxShadow = '0 5px 20px rgba(40, 167, 69, 0.4)';
            };
        } 
        // Estilo para preguntas Sí/No (preguntas 0 y 1)
        else {
            if (answer === "Sí") {
                button.className = 'btn';
                button.style.background = 'linear-gradient(135deg, #28a745, #1e7e34)';
                button.style.color = 'white';
                button.style.border = '2px solid white';
                button.style.outline = '2px solid #28a745';
                button.style.boxShadow = '0 5px 15px rgba(40, 167, 69, 0.4)';
                button.style.fontWeight = '800';
            } else {
                button.className = 'btn';
                button.style.background = 'rgba(220, 53, 69, 0.85)';
                button.style.color = 'white';
                button.style.border = '2px solid rgba(255, 255, 255, 0.7)';
                button.style.boxShadow = '0 5px 15px rgba(220, 53, 69, 0.3)';
            }
            
            // Efecto hover para Sí/No
            button.onmouseenter = () => {
                button.style.transform = 'translateY(-3px)';
                button.style.filter = 'brightness(1.1)';
            };
            button.onmouseleave = () => {
                button.style.transform = 'translateY(0)';
                button.style.filter = 'brightness(1)';
            };
        }

        button.textContent = answer;
        button.onclick = () => {
            if (currentQuestion === 1) { // Solo para la pregunta de deuda (índice 1)
                updateCampaignAndInitialize(answer);
            }
            if (currentQuestion === 2 || (answer === "Sí" && scoringQuestions.includes(currentQuestion))) {
                score++;
            }
            nextQuestion();
        };
        
        buttonsContainer.appendChild(button);
    });

    const progressPercent = Math.round((currentQuestion / questions.length) * 100) || 4;
    progress.style.width = `${progressPercent}%`;
    progress.textContent = `${progressPercent === 4 ? '0%' : `${progressPercent}%`}`;
}

function nextQuestion() {
    const questionElement = document.getElementById('quiz-question');
    const buttonsContainer = document.getElementById('quiz-buttons');

    buttonsContainer.classList.add('hidden');
    questionElement.style.opacity = 0;

    setTimeout(() => {
        currentQuestion++;
        if (currentQuestion < questions.length) {
            renderQuestion();
            questionElement.style.opacity = 1;
            buttonsContainer.classList.remove('hidden');
        } else {
            showResult();
        }
    }, 300);
}

function updateCampaignAndInitialize(response) {
    // Asignar valores de campaña basados en la respuesta (Sí/No)
    if (response === "Sí") {
        campaignValue = "314543"; // Valor para deudas > $7,000
    } else if (response === "No") {
        campaignValue = "310472"; // Valor para deudas <= $7,000
    }
    
    console.log("Valor de campaña actualizado a:", campaignValue);
    getHitIdAndInitMCC();
}

function getHitIdAndInitMCC() {
    const checkInterval = 20;
    const timeout = 40;
    let elapsedTime = 0;

    const intervalId = setInterval(() => {
        if (typeof flux !== 'undefined' && typeof flux.get === 'function') {
            ff_hit_id = flux.get('{hit}');
            if (ff_hit_id) {
                clearInterval(intervalId);
                console.log("Hit ID obtenido:", ff_hit_id);
                initMccScript(ff_hit_id);
                return;
            }
        }

        elapsedTime += checkInterval;
        if (elapsedTime >= timeout) {
            console.warn("Hit ID no disponible. Continuando sin Hit ID.");
            clearInterval(intervalId);
            initMccScript();
        }
    }, checkInterval);
}

function initMccScript(ff_hit_id) {
    (function (w, d, s, o, f, js, fjs) {
        w[o] = w[o] || function () { (w[o].q = w[o].q || []).push(arguments) };
        js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
        js.id = o;
        js.src = f;
        js.async = 1;
        fjs.parentNode.insertBefore(js, fjs);
    }(window, document, 'script', 'mcc', 'https://marketcall.com/js/mc-calltracking.js'));

    mcc('init', { site: 709, serviceBaseUrl: '//www.marketcall.com' });
    mcc('requestTrackingNumber', {
        campaign: campaignValue,
        selector: [{ 
            type: "dom", 
            value: "a[href^='tel:']"
        }],
        mask: "📞 Llama Ya: (XXX) XXX-XXXX",
        subid: ff_hit_id || '',
        subid1: ""
    });
}

function generarNumeroSolicitud() {
    const numero = Math.floor(1000 + Math.random() * 9000);
    return `SOL-${numero}`;
}

function showResult() {
    const quizContainer = document.querySelector('.quiz-container');
    const validationContainer = document.getElementById('validation-messages');
    const quizResult = document.getElementById('quiz-result');
    const solicitudNumeroElement = document.getElementById('solicitud-numero');

    quizContainer.style.display = 'none';
    validationContainer.style.display = 'block';

    let messageIndex = 0;
    const messageElement = validationContainer.querySelector('.validation-message');

    const interval = setInterval(() => {
        if (messageIndex < validationMessages.length) {
            messageElement.innerHTML = `
                <div class="d-flex align-items-center justify-content-center">
                    <span>${validationMessages[messageIndex].icon}</span>
                    <p class="ms-2 mb-0">${validationMessages[messageIndex].text}</p>
                </div>`;
            messageIndex++;
        } else {
            clearInterval(interval);
            validationContainer.style.display = 'none';
            quizResult.style.display = 'block';

            solicitudNumeroElement.textContent = generarNumeroSolicitud();
            
            // Scroll automático al resultado
            quizResult.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            setTimeout(() => {
                quizResult.style.opacity = '1';
            }, 300);
        }
    }, 1000);
}

function startCountdown(duration, display) {
    let timer = duration, minutes, seconds;
    const interval = setInterval(() => {
        minutes = Math.floor(timer / 60);
        seconds = timer % 60;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        display.textContent = `${minutes}:${seconds}`;
        if (--timer < 0) {
            clearInterval(interval);
            display.textContent = "0:00";
        }
    }, 1000);
}