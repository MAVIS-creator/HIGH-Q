document.addEventListener("DOMContentLoaded", () => {
  const studentName = localStorage.getItem("studentName") || "Unknown";
  const studentDept = localStorage.getItem("studentDept") || "N/A";
  const scores = JSON.parse(localStorage.getItem("examScores")) || {};

  const resultDiv = document.getElementById("resultContainer");
  if (!resultDiv) return;

  let total = 0;
  let subjectsCount = 0;

  let html = `<h2>Result for <span style="color:#b8860b">${studentName}</span> (${studentDept})</h2>`;
  html += `<table border="1">
             <tr><th>Subject</th><th>Score</th></tr>`;

  for (let subject in scores) {
    html += `<tr><td>${subject}</td><td>${scores[subject]}</td></tr>`;
    total += scores[subject];
    subjectsCount++;
  }

  let average = (subjectsCount > 0) ? (total / subjectsCount).toFixed(2) : 0;

  html += `<tr><th>Total</th><th>${total}</th></tr>`;
  html += `<tr><th>Average</th><th>${average}</th></tr>`;
  html += `</table>`;

  resultDiv.innerHTML = html;

  // --------- Save to admin-compatible storage ---------
  const allResults = JSON.parse(localStorage.getItem("quizResults")) || [];

  // Check if student already exists, update if yes
  const existingIndex = allResults.findIndex(r => r.studentName === studentName && r.studentDept === studentDept);

  const studentResult = {
    studentName,
    studentDept,
    scores,
    total,
    average,
    date: new Date().toLocaleString()
  };

  if (existingIndex >= 0) {
    allResults[existingIndex] = studentResult; // update existing
  } else {
    allResults.push(studentResult); // add new
  }

  localStorage.setItem("quizResults", JSON.stringify(allResults));
});

