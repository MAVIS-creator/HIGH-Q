document.addEventListener("DOMContentLoaded", () => {
  const questionContainer = document.getElementById("questionContainer");
  const subjectTabs = document.getElementById("subjectTabs");
  const numberNav = document.getElementById("numberNav");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const submitBtn = document.getElementById("submitBtn");
  const timerEl = document.getElementById("timer");

  let studentName = localStorage.getItem("studentName") || "Student";
  let selectedSubjects = JSON.parse(localStorage.getItem("selectedSubjects")) || [];

  // Each subject will have 40 questions
  let questionBank = {};
  let answers = {};
  let currentSubject = null;
  let currentIndex = 0;

  // ---------------- Timer ----------------
  let timeLeft = 2 * 60 * 60; // 2 hours in seconds
  function startTimer() {
    const interval = setInterval(() => {
      if (timeLeft <= 0) {
        clearInterval(interval);
        finishExam();
      } else {
        timeLeft--;
        const h = String(Math.floor(timeLeft / 3600)).padStart(2, "0");
        const m = String(Math.floor((timeLeft % 3600) / 60)).padStart(2, "0");
        const s = String(timeLeft % 60).padStart(2, "0");
        timerEl.textContent = `${h}:${m}:${s}`;
      }
    }, 1000);
  }

  // ---------------- Load Questions ----------------
  function shuffle(array) {
    return array.sort(() => Math.random() - 0.5);
  }

  function loadQuestions() {
    selectedSubjects.forEach(sub => {
      let pureSub = sub.split("_")[1] || sub; // strip department prefix
      let stored = JSON.parse(localStorage.getItem(`questions_${pureSub}`)) || [];
      let picked = shuffle(stored).slice(0, 40);
      questionBank[pureSub] = picked;
      answers[pureSub] = Array(picked.length).fill(null);
    });
  }

  // ---------------- Subject Tabs ----------------
  function renderTabs() {
    subjectTabs.innerHTML = "";
    Object.keys(questionBank).forEach(sub => {
      const btn = document.createElement("button");
      btn.textContent = sub;
      btn.className = "tabBtn";
      if (sub === currentSubject) btn.classList.add("active");
      btn.addEventListener("click", () => {
        currentSubject = sub;
        currentIndex = 0;
        renderQuestion();
        renderTabs();
      });
      subjectTabs.appendChild(btn);
    });
  }

  // ---------------- Render Question ----------------
  function renderQuestion() {
    const qList = questionBank[currentSubject];
    if (!qList || qList.length === 0) {
      questionContainer.innerHTML = `<p>No questions found for ${currentSubject}</p>`;
      return;
    }
    const q = qList[currentIndex];

    questionContainer.innerHTML = `
      <h3>${currentSubject} - Question ${currentIndex + 1} of ${qList.length}</h3>
      <p>${q.question}</p>
      <label><input type="radio" name="option" value="A" ${answers[currentSubject][currentIndex] === "A" ? "checked" : ""}> A. ${q.options.A}</label><br>
      <label><input type="radio" name="option" value="B" ${answers[currentSubject][currentIndex] === "B" ? "checked" : ""}> B. ${q.options.B}</label><br>
      <label><input type="radio" name="option" value="C" ${answers[currentSubject][currentIndex] === "C" ? "checked" : ""}> C. ${q.options.C}</label><br>
      <label><input type="radio" name="option" value="D" ${answers[currentSubject][currentIndex] === "D" ? "checked" : ""}> D. ${q.options.D}</label>
    `;

    document.querySelectorAll("input[name='option']").forEach(opt => {
      opt.addEventListener("change", e => {
        answers[currentSubject][currentIndex] = e.target.value;
        renderNumberNav(); // update color to green when answered
      });
    });

    renderNumberNav();
  }

  // ---------------- Number Navigation ----------------
  function renderNumberNav() {
    const qList = questionBank[currentSubject];
    numberNav.innerHTML = "";
    qList.forEach((_, i) => {
      const btn = document.createElement("button");
      btn.textContent = i + 1;

      if (i === currentIndex) btn.classList.add("active");
      if (answers[currentSubject][i]) btn.classList.add("answered");

      btn.addEventListener("click", () => {
        currentIndex = i;
        renderQuestion();
      });

      numberNav.appendChild(btn);
    });
  }

  // ---------------- Exam Controls ----------------
  prevBtn.addEventListener("click", () => {
    if (currentIndex > 0) {
      currentIndex--;
      renderQuestion();
    }
  });

  nextBtn.addEventListener("click", () => {
    if (currentIndex < questionBank[currentSubject].length - 1) {
      currentIndex++;
      renderQuestion();
    }
  });

  submitBtn.addEventListener("click", () => {
    if (confirm("Are you sure you want to submit the exam?")) {
      finishExam();
    }
  });
function finishExam() {
  // Calculate scores per subject
  let examScores = {};
  Object.keys(questionBank).forEach(sub => {
    let score = 0;
    questionBank[sub].forEach((q, i) => {
      if (answers[sub][i] === q.correct) {
        score++;
      }
    });
    examScores[sub] = score;
  });

  // Get student info
  let studentName = localStorage.getItem("studentName") || "Unknown";
  let studentDept = localStorage.getItem("examDepartment") || "General";

  // ✅ Save individual result for student page
  localStorage.setItem("examScores", JSON.stringify(examScores));
  localStorage.setItem("studentName", studentName);
  localStorage.setItem("studentDept", studentDept);

  // ✅ Save into all results for admin page
  let allResults = JSON.parse(localStorage.getItem("allExamResults")) || [];
  allResults.push({
    name: studentName,
    department: studentDept,
    scores: examScores,
    date: new Date().toLocaleString()
  });
  localStorage.setItem("allExamResults", JSON.stringify(allResults));

  alert("Exam Finished! Submissions saved.");
  window.location.href = "result.html"; // student result page
  document.getElementById("submitQuiz").addEventListener("click", () => {
    const studentName = document.getElementById("studentName").value.trim();
    const subject = document.getElementById("subject").value;
    const score = parseFloat(document.getElementById("score").value);
    const total = parseFloat(document.getElementById("total").value);

    if (!studentName || isNaN(score) || isNaN(total)) {
        alert("Please fill all fields correctly!");
        return;
    }

    // Save the result
    const results = JSON.parse(localStorage.getItem("quizResults")) || [];
    results.push({
        studentName,
        subject,
        score,
        total,
        date: new Date().toLocaleString()
    });
    localStorage.setItem("quizResults", JSON.stringify(results));

    // Optional alert
    alert("Quiz submitted successfully! Redirecting to Admin page...");

    // Redirect to admin page
    window.location.href = "admin.html";
});

}
  function calculateScoreForSubject(subjectName) {
    let subjectQuestions = allQuestions[subjectName] || [];
    let score = 0;

    subjectQuestions.forEach((q, index) => {
        let chosen = userAnswers[subjectName]?.[index];
        if (chosen && chosen === q.answer) {
            score++;
        }
    });

    return score; // Number of correct answers
}


  // ---------------- Calculator ----------------
  const calc = document.getElementById("calculator");
  const toggleCalc = document.getElementById("toggleCalc");
  const display = document.getElementById("calcDisplay");
  const buttonsDiv = document.getElementById("calcButtons");

  const calcButtons = [
    "7","8","9","/","4","5","6","*","1","2","3","-","0",".","=","+","C"
  ];

  calcButtons.forEach(b => {
    const btn = document.createElement("button");
    btn.textContent = b;
    btn.addEventListener("click", () => handleCalc(b));
    buttonsDiv.appendChild(btn);
  });

  function handleCalc(val) {
    if (val === "C") {
      display.value = "";
    } else if (val === "=") {
      try {
        display.value = eval(display.value);
      } catch {
        display.value = "Error";
      }
    } else {
      display.value += val;
    }
  }

  toggleCalc.addEventListener("click", () => {
    calc.classList.toggle("hidden");
  });

  // ---------------- Init ----------------
  loadQuestions();
  currentSubject = Object.keys(questionBank)[0];
  renderTabs();
  renderQuestion();
  startTimer();
});
// Display student name
document.addEventListener("DOMContentLoaded", () => {
    const studentName = localStorage.getItem("studentName");
    if (studentName) {
        document.getElementById("studentName").textContent = studentName;
    } else {
        document.getElementById("studentName").textContent = "Guest";
    }
});
function endExam() {
    clearInterval(timer);

    // 📝 Collect scores per subject
    let examScores = {};

    selectedSubjects.forEach(sub => {
        let subjectName = sub.split("_")[1]; // e.g. Science_Mathematics → Mathematics
        let score = calculateScoreForSubject(subjectName); // 👈 function we’ll define
        examScores[subjectName] = score;
    });

    // Save to localStorage
    localStorage.setItem("examScores", JSON.stringify(examScores));
    localStorage.setItem("studentName", studentName);
    localStorage.setItem("studentDept", studentDept);

    // Redirect to result page
    window.location.href = "result.html";
}





