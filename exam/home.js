// js/index.js
document.addEventListener("DOMContentLoaded", () => {
  try {
    // ---------------- Subjects grouped by department ----------------
    const subjectsByDept = {
      Science: ["English", "Mathematics", "Biology", "Physics", "Chemistry"],
      Arts: ["English", "Government", "CRS", "Literature", "Economics"],
      Commercial: ["English", "Accounting", "Economics", "Commerce", "Business Studies"]
    };

    // ---------------- Get page elements ----------------
    const departmentSelect = document.getElementById("department");
    const subjectList = document.getElementById("subjectList");
    const examForm = document.getElementById("examForm");
    const nameInput = document.getElementById("name");

    if (!departmentSelect || !subjectList || !examForm || !nameInput) {
      console.error("❌ Required index.html elements not found (department, subjectList, examForm, name).");
      return;
    }

    // ---------------- Selected count display ----------------
    let selectedCountEl = document.getElementById("selectedCount");
    if (!selectedCountEl) {
      selectedCountEl = document.createElement("div");
      selectedCountEl.id = "selectedCount";
      selectedCountEl.style.marginTop = "8px";
      selectedCountEl.style.fontWeight = "bold";
      subjectList.parentNode.insertBefore(selectedCountEl, subjectList.nextSibling);
    }

    // ---------------- Render subjects based on department ----------------
    function renderSubjects() {
      const dept = departmentSelect.value;
      const list = subjectsByDept[dept] || [];
      subjectList.innerHTML = "";

      list.forEach(sub => {
        const label = document.createElement("label");
        label.style.display = "inline-block";
        label.style.margin = "6px";

        const input = document.createElement("input");
        input.type = "checkbox";
        input.className = "subject-checkbox";
        input.value = sub;

        // English is compulsory
        if (sub === "English") {
          input.checked = true;
          input.disabled = true;
        }

        const text = document.createTextNode(" " + sub);
        label.appendChild(input);
        label.appendChild(text);
        subjectList.appendChild(label);
      });

      attachCheckboxHandlers();
      updateSelectedCount();
    }

    // ---------------- Limit subject selection ----------------
    function attachCheckboxHandlers() {
      const checkboxes = Array.from(document.querySelectorAll(".subject-checkbox"));
      checkboxes.forEach(cb => {
        cb.onchange = null; // reset
        cb.addEventListener("change", () => {
          const checked = Array.from(document.querySelectorAll(".subject-checkbox:checked"));
          if (checked.length > 4) {
            cb.checked = false;
            alert("⚠️ You can only select 4 subjects (English is compulsory).");
          }
          updateSelectedCount();
        });
      });
    }

    // ---------------- Update subject count display ----------------
    function updateSelectedCount() {
      const checked = document.querySelectorAll(".subject-checkbox:checked").length;
      selectedCountEl.textContent = `Selected: ${checked}/4`;
    }

    // ---------------- Initial load ----------------
    renderSubjects();
    departmentSelect.addEventListener("change", renderSubjects);

    // ---------------- Form submission ----------------
    examForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const name = nameInput.value.trim();
      if (!name) {
        alert("⚠️ Please enter your name before starting the exam.");
        nameInput.focus();
        return;
      }

      const department = departmentSelect.value;
      if (!department) {
        alert("⚠️ Please select a department.");
        return;
      }

      const checkedBoxes = Array.from(document.querySelectorAll(".subject-checkbox:checked"));
      const selectedSubjects = checkedBoxes.map(cb => `${department}_${cb.value}`); // Save as Department_Subject format

      // Ensure English is included
      if (!selectedSubjects.some(sub => sub.endsWith("_English"))) {
        selectedSubjects.unshift(`${department}_English`);
      }

      if (selectedSubjects.length !== 4) {
        alert("⚠️ Please select exactly 3 additional subjects (English is already compulsory). Total should be 4.");
        return;
      }

      try {
        // Save to localStorage
        localStorage.setItem("studentName", name);
        localStorage.setItem("selectedSubjects", JSON.stringify(selectedSubjects));
        localStorage.setItem("examDepartment", department);

        // Redirect to quiz page
        window.location.href = "quiz.html";
      } catch (err) {
        console.error("❌ Failed to save exam setup to localStorage:", err);
        alert("An error occurred while starting the exam. Please try again.");
      }
    });

  } catch (err) {
    console.error("❌ index.js initialization error:", err);
  }
});

