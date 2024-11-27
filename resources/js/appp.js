
document.addEventListener('DOMContentLoaded', () => {
  const emailList = document.getElementById('emailList');
  const emailContent = document.getElementById('emailContent');
  const summaryContent = document.getElementById('summaryContent');
  const generateDailySummaryBtn = document.getElementById('generateDailySummary');
  const currentDateDisplay = document.getElementById('currentDate');
  

  // Load current date in the daily summaries section
  if (currentDateDisplay) {
      const currentDate = new Date().toLocaleDateString();
      currentDateDisplay.textContent = currentDate;
  }

  if (emailList) {
      // Load emails into inbox
      emails.forEach(email => {
          const li = document.createElement('li');
          li.textContent = email.subject;
          li.dataset.id = email.id;
          li.addEventListener('click', () => loadEmailContent(email.id));
          emailList.appendChild(li);
      });
  }

  // load email content into textarea
  function loadEmailContent(emailId) {
      const selectedEmail = emails.find(email => email.id == emailId);
      if (emailContent) {
          emailContent.value = selectedEmail.content;
      }
  }

  // Summarize button action
  if (document.getElementById('summarizeBtn')) {
      document.getElementById('summarizeBtn').addEventListener('click', () => {
          const content = emailContent.value;
          if (content) {
              summaryContent.value = "Summarized: " + content.split(" ").slice(0, 10).join(" ") + "...";
          } else {
              summaryContent.value = "No email content to summarize.";
          }
      });
  }


// Call saveDailySummary at the end of the day 
setTimeout(() => {
  saveDailySummary();
}, 1000 * 60 * 60 * 24); 


  // Generate daily summaries
  if (generateDailySummaryBtn) {
      generateDailySummaryBtn.addEventListener('click', () => {
          const currentDate = new Date().toLocaleDateString();
          const dailySummaries = emails.map(email => ({
              subject: email.subject,
              summary: summarizeEmail(email.content)
          }));
      });
  }

  // Function to summarize an email
  function summarizeEmail(content) {
      return "Summarized: " + content.split(" ").slice(0, 10).join(" ") + "...";
  }

});
