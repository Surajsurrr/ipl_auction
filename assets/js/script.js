// Auction functionality
let auctionInterval;

// Auto-refresh auction status
function startAuctionRefresh() {
    auctionInterval = setInterval(() => {
        refreshAuctionStatus();
    }, 3000); // Refresh every 3 seconds
}

function stopAuctionRefresh() {
    if (auctionInterval) {
        clearInterval(auctionInterval);
    }
}

// Refresh auction status via AJAX
function refreshAuctionStatus() {
    fetch('ajax/get_auction_status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAuctionUI(data);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update auction UI
function updateAuctionUI(data) {
    if (data.current_player) {
        document.getElementById('current-player-name').textContent = data.current_player.player_name;
        document.getElementById('current-bid-amount').textContent = formatCurrency(data.current_bid);
        
        if (data.current_bidder) {
            document.getElementById('current-bidder').textContent = data.current_bidder.team_name;
        }
    }
}

// Place bid
function placeBid(teamId, increment = 1000000) {
    const currentBid = parseFloat(document.getElementById('current-bid-amount').dataset.value);
    const newBid = currentBid + increment;
    
    fetch('ajax/place_bid.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            team_id: teamId,
            bid_amount: newBid
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            refreshAuctionStatus();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error placing bid', 'error');
    });
}

// Get next player
function getNextPlayer(group) {
    fetch('ajax/get_next_player.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            group: group
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('New player on the block!', 'success');
            refreshAuctionStatus();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error getting next player', 'error');
    });
}

// Finalize sale
function finalizeSale() {
    if (!confirm('Are you sure you want to finalize this sale?')) {
        return;
    }
    
    fetch('ajax/finalize_sale.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error finalizing sale', 'error');
    });
}

// Pass player (unsold)
function passPlayer() {
    if (!confirm('Pass on this player?')) {
        return;
    }
    
    fetch('ajax/pass_player.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Player passed', 'info');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error passing player', 'error');
    });
}

// Format currency
function formatCurrency(amount) {
    const crores = amount / 10000000;
    return crores.toFixed(2) + ' Cr';
}

// Show alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Filter players
function filterPlayers() {
    const type = document.getElementById('filter-type').value;
    const group = document.getElementById('filter-group').value;
    const sold = document.getElementById('filter-sold').value;
    
    let url = 'players.php?';
    if (type) url += `type=${type}&`;
    if (group) url += `group=${group}&`;
    if (sold) url += `sold=${sold}&`;
    
    window.location.href = url;
}

// Confirm action
function confirmAction(message) {
    return confirm(message);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Start auction refresh if on auction page
    if (document.getElementById('auction-container')) {
        startAuctionRefresh();
    }
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopAuctionRefresh();
});
