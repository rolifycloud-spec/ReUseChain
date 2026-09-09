jQuery(document).ready(function ($) {
    const $button = $('#sanctions-check-all');
    const $output = $('#sanctions-check-results');

    function checkTableReadyAndToggleButton() {
        const $rows = $('table.jet-dynamic-table tbody tr');
        if ($rows.length === 0) {
            return false; // wait more
        }

        // Check if any row has "Na čekanju"
        let hasPending = false;
        $rows.each(function () {
            const status = $(this).find('td:last-child').text().trim();
            if (status === 'Na čekanju') {
                hasPending = true;
                return false; // break
            }
        });

        if (!hasPending) {
            $button.hide();
        }

        return true; // table is ready
    }

    // ⏳ Wait until table is populated
    const waitForTable = setInterval(() => {
        const ready = checkTableReadyAndToggleButton();
        if (ready) {
            clearInterval(waitForTable);
        }
    }, 300); // check every 300ms

    // ✅ Button click logic
    $button.on('click', function () {
        console.log('🔘 Batch button clicked');
        $output.html('⏳ Prikupljam podatke...');

        const companies = [];
        $('table.jet-dynamic-table tbody tr').each(function () {
            const $row = $(this);
            const status = $row.find('td:last-child').text().trim();

            if (status === 'Na čekanju') {
                const $link = $row.find('td.jet-dynamic-table__col--kompanija a');
                const companyName = $link.text().trim();
                const profileLink = $link.attr('href');
                const userIdMatch = profileLink.match(/korisnik\/(\d+)\//);
                const userId = userIdMatch ? userIdMatch[1] : null;

                if (companyName && userId) {
                    companies.push({ user_id: userId, name: companyName });
                }
            }
        });

        if (!companies.length) {
            $output.html('<p style="color:red;">❌ Nema kompanija za provjeru.</p>');
            return;
        }

        $output.html('⏳ Provjeravam ' + companies.length + ' kompanija...');

        $.post(SanctionsCheck.ajax_url, {
            action: 'sanctions_check_all_pending',
            nonce: SanctionsCheck.nonce,
            companies: companies
        }, function (response) {
            if (response.success) {
                $output.html(response.data);
            } else {
                $output.html('<p style="color:red;">❌ Greška: ' + response.data.message + '</p>');
            }
        });
    });
});
