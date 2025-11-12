document.addEventListener("DOMContentLoaded", () => {
  const resultsTableBody = document.getElementById("resultsTableBody");
  const deptFilter = document.getElementById("deptFilter"); // optional filter by dept
  const exportBtn = document.getElementById("exportBtn");

  let results = [];

  // Load results from localStorage
  function loadResults() {
    results = JSON.parse(localStorage.getItem("quizResults")) || [];

    // Populate dept filter
    if (deptFilter) {
      const currentDepts = Array.from(deptFilter.options).map(o => o.value);
      results.forEach(r => {
        if (!currentDepts.includes(r.studentDept)) {
          const option = document.createElement("option");
          option.value = r.studentDept;
          option.textContent = r.studentDept;
          deptFilter.appendChild(option);
        }
      });
    }
  }

  // Display results
  function displayResults(filterDept = "All") {
    resultsTableBody.innerHTML = "";
    const filteredResults = filterDept === "All" ? results : results.filter(r => r.studentDept === filterDept);

    filteredResults.forEach(r => {
      const subjects = Object.keys(r.scores).join(", ");
      const scores = Object.values(r.scores).join(", ");

      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${r.studentName}</td>
        <td>${r.studentDept}</td>
        <td>${subjects}</td>
        <td>${scores}</td>
        <td>${r.total}</td>
        <td>${r.average}</td>
        <td>${r.date}</td>
      `;
      resultsTableBody.appendChild(row);
    });
  }

  loadResults();
  displayResults();

  if (deptFilter) {
    deptFilter.addEventListener("change", () => {
      displayResults(deptFilter.value);
    });
  }

  // Live update every 1 second
  setInterval(() => {
    const latestResults = JSON.parse(localStorage.getItem("quizResults")) || [];
    if (JSON.stringify(latestResults) !== JSON.stringify(results)) {
      loadResults();
      displayResults(deptFilter ? deptFilter.value : "All");
    }
  }, 1000);

  // --------- Export to CSV (clean format) ---------
  if (exportBtn) {
    exportBtn.addEventListener("click", () => {
      if (results.length === 0) {
        alert("No results to export!");
        return;
      }

      // Collect all unique subjects
      const allSubjects = new Set();
      results.forEach(r => Object.keys(r.scores).forEach(sub => allSubjects.add(sub)));
      const subjectsArray = Array.from(allSubjects);

      // Prepare CSV header
      let csv = ["Student Name", "Department", ...subjectsArray, "Total", "Average", "Date"].join(",") + "\n";

      // Add each student's data
      results.forEach(r => {
        const row = [
          `"${r.studentName}"`,
          `"${r.studentDept}"`,
          ...subjectsArray.map(sub => r.scores[sub] !== undefined ? r.scores[sub] : ""),
          r.total,
          r.average,
          `"${r.date}"`
        ];
        csv += row.join(",") + "\n";
      });

      // Download CSV
      const blob = new Blob([csv], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "quiz_results.csv";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    });
  }
});
const clearBtn = document.getElementById("clearBtn");

if (clearBtn) {
  clearBtn.addEventListener("click", () => {
    const confirmClear = confirm("Are you sure you want to clear all results? This cannot be undone.");
    if (confirmClear) {
      localStorage.removeItem("quizResults");
      results = [];
      resultsTableBody.innerHTML = "";
      alert("All results have been cleared.");
      
      // Reset dept filter
      if (deptFilter) {
        deptFilter.innerHTML = '<option value="All">All</option>';
      }
    }
  });
}

