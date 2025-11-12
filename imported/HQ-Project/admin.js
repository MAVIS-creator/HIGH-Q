document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("questionForm");
  const list = document.getElementById("questionList");

  function loadQuestions(subject) {
    return JSON.parse(localStorage.getItem(`questions_${subject}`)) || [];
  }

  function saveQuestions(subject, questions) {
    localStorage.setItem(`questions_${subject}`, JSON.stringify(questions));
  }

  function renderQuestions() {
    list.innerHTML = "";
    const subjects = [
      "English", "Mathematics", "Biology", "Physics", "Chemistry",
      "Government", "CRS", "Literature", "Economics", "Accounting",
      "Commerce", "Business Studies"
    ];

    subjects.forEach(sub => {
      const qList = loadQuestions(sub);
      if (qList.length > 0) {
        const section = document.createElement("div");
        section.innerHTML = `<h3>${sub}</h3>`;
        qList.forEach((q, i) => {
          const div = document.createElement("div");
          div.className = "questionItem";
          div.innerHTML = `
            <p><b>Q${i + 1}:</b> ${q.question}</p>
            <ul>
              <li>A. ${q.options.A}</li>
              <li>B. ${q.options.B}</li>
              <li>C. ${q.options.C}</li>
              <li>D. ${q.options.D}</li>
            </ul>
            <p><b>Answer:</b> ${q.answer}</p>
            <button data-sub="${sub}" data-index="${i}" class="editBtn">Edit</button>
            <button data-sub="${sub}" data-index="${i}" class="delBtn">Delete</button>
          `;
          section.appendChild(div);
        });
        list.appendChild(section);
      }
    });

    // Delete
    document.querySelectorAll(".delBtn").forEach(btn => {
      btn.addEventListener("click", e => {
        const sub = e.target.dataset.sub;
        const idx = e.target.dataset.index;
        const qList = loadQuestions(sub);
        qList.splice(idx, 1);
        saveQuestions(sub, qList);
        renderQuestions();
      });
    });

    // Edit
    document.querySelectorAll(".editBtn").forEach(btn => {
      btn.addEventListener("click", e => {
        const sub = e.target.dataset.sub;
        const idx = e.target.dataset.index;
        const qList = loadQuestions(sub);
        const q = qList[idx];

        document.getElementById("subject").value = sub;
        document.getElementById("question").value = q.question;
        document.getElementById("optA").value = q.options.A;
        document.getElementById("optB").value = q.options.B;
        document.getElementById("optC").value = q.options.C;
        document.getElementById("optD").value = q.options.D;
        document.getElementById("answer").value = q.answer;

        qList.splice(idx, 1);
        saveQuestions(sub, qList);
        renderQuestions();
      });
    });
  }

  form.addEventListener("submit", e => {
    e.preventDefault();
    const subject = document.getElementById("subject").value;
    const question = document.getElementById("question").value.trim();
    const options = {
      A: document.getElementById("optA").value.trim(),
      B: document.getElementById("optB").value.trim(),
      C: document.getElementById("optC").value.trim(),
      D: document.getElementById("optD").value.trim()
    };
    const answer = document.getElementById("answer").value;

    if (!question || !options.A || !options.B || !options.C || !options.D) {
      alert("Please fill all fields.");
      return;
    }

    const qList = loadQuestions(subject);
    qList.push({ question, options, answer });
    saveQuestions(subject, qList);

    form.reset();
    renderQuestions();
  });

  renderQuestions();
});

