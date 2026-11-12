document.addEventListener("DOMContentLoaded", function () {
    const incomeForm = document.getElementById("incomeForm");
    const incomeTable = document.getElementById("incomeTable");

    if (!incomeForm || !incomeTable) return;

    const incomeAmount = document.getElementById("incomeAmount");
    const incomeName = document.getElementById("incomeName");
    const incomeCategory = document.getElementById("incomeCategory");
    const incomeDate = document.getElementById("incomeDate");
    const incomeDescription = document.getElementById("incomeDescription");

    const summaryCards = document.querySelectorAll(
        ".transaction-summary .summary-card h3"
    );

    let transactions = [];

    function formatRupiah(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(amount);
    }

    function renderTransactions() {
        incomeTable.innerHTML = "";

        if (transactions.length === 0) {
            incomeTable.innerHTML = `
                <tr>
                    <td colspan="5" class="table-empty">
                        Belum ada data pemasukan.
                    </td>
                </tr>
            `;
        } else {
            transactions.forEach(item => {
                const row = document.createElement("tr");

                [
                    item.date,
                    item.name,
                    item.category,
                    formatRupiah(item.amount),
                    item.description || "-"
                ].forEach(value => {
                    const cell = document.createElement("td");
                    cell.textContent = value;
                    row.appendChild(cell);
                });

                incomeTable.appendChild(row);
            });
        }

        const total = transactions.reduce(
            (sum, item) => sum + item.amount, 0
        );

        if (summaryCards.length >= 2) {
            summaryCards[0].textContent = formatRupiah(total);
            summaryCards[1].textContent = transactions.length;
        }
    }

    incomeForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const amount = Number(incomeAmount.value);

        if (!Number.isFinite(amount) || amount <= 0) {
            alert("Nominal harus lebih dari 0!");
            return;
        }

        const transaction = {
            name: incomeName.value.trim(),
            amount: amount,
            category: incomeCategory.value,
            date: incomeDate.value,
            description: incomeDescription.value.trim()
        };

        transactions.unshift(transaction);

        renderTransactions();
        incomeForm.reset();

        alert("Pemasukan berhasil ditambahkan!");
    });

    renderTransactions();
});