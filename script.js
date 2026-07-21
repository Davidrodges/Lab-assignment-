document.addEventListener("DOMContentLoaded", () => {
  setupWelcomeMessage();
  setupOrderFormValidation();
  setupHomeInteractions();
  setupShopInteractions();
});

function setupWelcomeMessage() {
  const welcomeMessage = document.getElementById("welcomeMessage");

  if (!welcomeMessage) {
    return;
  }

  let customerName = localStorage.getItem("customerName");

  if (!customerName) {
    const userInput = window.prompt("Welcome to SoleMarket Kenya! Please enter your name:");

    if (userInput && userInput.trim()) {
      customerName = userInput.trim();
      localStorage.setItem("customerName", customerName);
    } else {
      customerName = "Guest";
    }
  }

  welcomeMessage.textContent = "Karibu, " + customerName + "! Explore our latest shoe collection.";
}

function setupOrderFormValidation() {
  const form = document.getElementById("orderForm");

  if (!form) {
    return;
  }

  form.addEventListener("submit", (event) => {
    const missingFields = [];

    clearFieldErrors();
    clearFormMessages();

    validateTextInput("fullName", "Full name", missingFields);
    validateTextInput("phone", "Phone number", missingFields);
    validateSelectInput("location", "Delivery location", missingFields);
    validateSelectInput("shoeType", "Shoe type", missingFields);
    validateRadioInput("condition", "Condition", missingFields);
    validateTextInput("size", "Shoe size", missingFields);

    if (missingFields.length > 0) {
      event.preventDefault();
      showFormError("Please complete all required fields: " + missingFields.join(", ") + ".");
      return;
    }

    event.preventDefault();
    showFormSuccess("Order submitted successfully. We will contact you shortly.");
    form.reset();
  });
}

function validateTextInput(id, label, missingFields) {
  const input = document.getElementById(id);

  if (!input) {
    return;
  }

  if (!input.value.trim()) {
    input.classList.add("input-error");
    setFieldError(id + "Error", label + " is required.");
    missingFields.push(label);
  }
}

function validateSelectInput(id, label, missingFields) {
  const select = document.getElementById(id);

  if (!select) {
    return;
  }

  if (!select.value.trim()) {
    select.classList.add("input-error");
    setFieldError(id + "Error", label + " is required.");
    missingFields.push(label);
  }
}

function validateRadioInput(name, label, missingFields) {
  const options = document.querySelectorAll('input[name="' + name + '"]');
  const checkedOption = document.querySelector('input[name="' + name + '"]:checked');

  if (options.length === 0) {
    return;
  }

  if (!checkedOption) {
    options.forEach((option) => {
      option.classList.add("input-error");
    });
    setFieldError(name + "Error", label + " is required.");
    missingFields.push(label);
  }
}

function setFieldError(errorId, message) {
  const errorElement = document.getElementById(errorId);

  if (errorElement) {
    errorElement.textContent = message;
  }
}

function clearFieldErrors() {
  const allFieldErrors = document.querySelectorAll(".field-error");
  const allInputErrors = document.querySelectorAll(".input-error");

  allFieldErrors.forEach((fieldError) => {
    fieldError.textContent = "";
  });

  allInputErrors.forEach((inputError) => {
    inputError.classList.remove("input-error");
  });
}

function showFormError(message) {
  const formError = document.getElementById("formError");

  if (formError) {
    formError.textContent = message;
  }
}

function showFormSuccess(message) {
  const formSuccess = document.getElementById("formSuccess");

  if (formSuccess) {
    formSuccess.textContent = message;
  }
}

function clearFormMessages() {
  const formError = document.getElementById("formError");
  const formSuccess = document.getElementById("formSuccess");

  if (formError) {
    formError.textContent = "";
  }

  if (formSuccess) {
    formSuccess.textContent = "";
  }
}

function setupHomeInteractions() {
  const toggleButton = document.getElementById("toggleTipsBtn");
  const tipsPanel = document.getElementById("tipsPanel");

  if (!toggleButton || !tipsPanel) {
    return;
  }

  toggleButton.addEventListener("click", () => {
    const panelHidden = tipsPanel.hasAttribute("hidden");

    if (panelHidden) {
      tipsPanel.removeAttribute("hidden");
      toggleButton.textContent = "Hide Buying Tips";
    } else {
      tipsPanel.setAttribute("hidden", "");
      toggleButton.textContent = "Show Buying Tips";
    }
  });
}

function setupShopInteractions() {
  const highlightButton = document.getElementById("highlightDealsBtn");
  const dealMessage = document.getElementById("dealMessage");
  const priceCards = document.querySelectorAll(".price-card");

  if (!highlightButton || !dealMessage || priceCards.length === 0) {
    return;
  }

  highlightButton.addEventListener("click", () => {
    const isActive = highlightButton.classList.toggle("active-state");

    if (isActive) {
      priceCards.forEach((card) => {
        card.classList.add("card-highlight");
      });
      dealMessage.textContent = "Great picks! Thrift pairs offer the best value this week.";
      highlightButton.textContent = "Clear Highlights";
    } else {
      priceCards.forEach((card) => {
        card.classList.remove("card-highlight");
      });
      dealMessage.textContent = "";
      highlightButton.textContent = "Highlight Best Deals";
    }
  });
}
