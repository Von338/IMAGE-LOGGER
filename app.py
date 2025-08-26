from flask import Flask, render_template, request, redirect, url_for, session, jsonify, flash, send_file
from werkzeug.security import generate_password_hash, check_password_hash
import sqlite3
import os
import json
from datetime import datetime
import stripe
from functools import wraps

app = Flask(__name__)
app.secret_key = 'your-secret-key-change-this'

# Stripe configuration (replace with your keys)
stripe.api_key = 'sk_test_...'  # Your Stripe secret key
STRIPE_PUBLISHABLE_KEY = 'pk_test_...'  # Your Stripe publishable key

# Database setup
DATABASE = 'gamemarket.db'

def init_db():
    """Initialize the database with required tables"""
    conn = sqlite3.connect(DATABASE)
    cursor = conn.cursor()
    
    # Users table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Games table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url TEXT,
            download_url TEXT,
            developer TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')
    
    # Purchases table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            game_id INTEGER,
            purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            stripe_payment_id TEXT,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (game_id) REFERENCES games (id)
        )
    ''')
    
    # Cart table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS cart (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            game_id INTEGER,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (game_id) REFERENCES games (id)
        )
    ''')
    
    conn.commit()
    conn.close()

def get_db_connection():
    """Get database connection"""
    conn = sqlite3.connect(DATABASE)
    conn.row_factory = sqlite3.Row
    return conn

def login_required(f):
    """Decorator to require login"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

# Routes

@app.route('/')
def index():
    """Homepage with game catalog"""
    conn = get_db_connection()
    games = conn.execute('SELECT * FROM games ORDER BY created_at DESC').fetchall()
    conn.close()
    
    return render_template('index.html', games=games)

@app.route('/register', methods=['GET', 'POST'])
def register():
    """User registration"""
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']
        
        if not email or not password:
            flash('Email and password are required')
            return render_template('register.html')
        
        conn = get_db_connection()
        
        # Check if user already exists
        existing_user = conn.execute('SELECT id FROM users WHERE email = ?', (email,)).fetchone()
        if existing_user:
            flash('Email already registered')
            conn.close()
            return render_template('register.html')
        
        # Create new user
        password_hash = generate_password_hash(password)
        conn.execute('INSERT INTO users (email, password_hash) VALUES (?, ?)', 
                    (email, password_hash))
        conn.commit()
        conn.close()
        
        flash('Registration successful! Please login.')
        return redirect(url_for('login'))
    
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    """User login"""
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']
        
        conn = get_db_connection()
        user = conn.execute('SELECT * FROM users WHERE email = ?', (email,)).fetchone()
        conn.close()
        
        if user and check_password_hash(user['password_hash'], password):
            session['user_id'] = user['id']
            session['user_email'] = user['email']
            return redirect(url_for('index'))
        else:
            flash('Invalid email or password')
    
    return render_template('login.html')

@app.route('/logout')
def logout():
    """User logout"""
    session.clear()
    return redirect(url_for('index'))

@app.route('/game/<int:game_id>')
def game_detail(game_id):
    """Game detail page"""
    conn = get_db_connection()
    game = conn.execute('SELECT * FROM games WHERE id = ?', (game_id,)).fetchone()
    
    # Check if user owns this game
    owns_game = False
    if 'user_id' in session:
        purchase = conn.execute(
            'SELECT * FROM purchases WHERE user_id = ? AND game_id = ?',
            (session['user_id'], game_id)
        ).fetchone()
        owns_game = purchase is not None
    
    conn.close()
    
    if not game:
        flash('Game not found')
        return redirect(url_for('index'))
    
    return render_template('game_detail.html', game=game, owns_game=owns_game)

@app.route('/add_to_cart/<int:game_id>')
@login_required
def add_to_cart(game_id):
    """Add game to cart"""
    conn = get_db_connection()
    
    # Check if already in cart
    existing = conn.execute(
        'SELECT * FROM cart WHERE user_id = ? AND game_id = ?',
        (session['user_id'], game_id)
    ).fetchone()
    
    if not existing:
        conn.execute(
            'INSERT INTO cart (user_id, game_id) VALUES (?, ?)',
            (session['user_id'], game_id)
        )
        conn.commit()
        flash('Game added to cart!')
    else:
        flash('Game already in cart')
    
    conn.close()
    return redirect(url_for('game_detail', game_id=game_id))

@app.route('/cart')
@login_required
def cart():
    """View shopping cart"""
    conn = get_db_connection()
    cart_items = conn.execute('''
        SELECT g.*, c.id as cart_id 
        FROM cart c 
        JOIN games g ON c.game_id = g.id 
        WHERE c.user_id = ?
    ''', (session['user_id'],)).fetchall()
    
    total = sum(item['price'] for item in cart_items)
    conn.close()
    
    return render_template('cart.html', cart_items=cart_items, total=total)

@app.route('/remove_from_cart/<int:cart_id>')
@login_required
def remove_from_cart(cart_id):
    """Remove item from cart"""
    conn = get_db_connection()
    conn.execute('DELETE FROM cart WHERE id = ? AND user_id = ?', 
                (cart_id, session['user_id']))
    conn.commit()
    conn.close()
    
    flash('Item removed from cart')
    return redirect(url_for('cart'))

@app.route('/checkout')
@login_required
def checkout():
    """Checkout page"""
    conn = get_db_connection()
    cart_items = conn.execute('''
        SELECT g.*, c.id as cart_id 
        FROM cart c 
        JOIN games g ON c.game_id = g.id 
        WHERE c.user_id = ?
    ''', (session['user_id'],)).fetchall()
    
    if not cart_items:
        flash('Your cart is empty')
        return redirect(url_for('cart'))
    
    total = sum(item['price'] for item in cart_items)
    conn.close()
    
    return render_template('checkout.html', cart_items=cart_items, total=total, 
                         stripe_public_key=STRIPE_PUBLISHABLE_KEY)

@app.route('/library')
@login_required
def library():
    """User's game library"""
    conn = get_db_connection()
    purchased_games = conn.execute('''
        SELECT g.*, p.purchase_date 
        FROM purchases p 
        JOIN games g ON p.game_id = g.id 
        WHERE p.user_id = ? 
        ORDER BY p.purchase_date DESC
    ''', (session['user_id'],)).fetchall()
    conn.close()
    
    return render_template('library.html', games=purchased_games)

@app.route('/download/<int:game_id>')
@login_required
def download_game(game_id):
    """Download purchased game"""
    conn = get_db_connection()
    
    # Verify user owns the game
    purchase = conn.execute(
        'SELECT * FROM purchases WHERE user_id = ? AND game_id = ?',
        (session['user_id'], game_id)
    ).fetchone()
    
    if not purchase:
        flash('You do not own this game')
        return redirect(url_for('library'))
    
    game = conn.execute('SELECT * FROM games WHERE id = ?', (game_id,)).fetchone()
    conn.close()
    
    if not game or not game['download_url']:
        flash('Download not available')
        return redirect(url_for('library'))
    
    # In a real implementation, you would serve the actual game file
    # For now, we'll just redirect to the download URL
    return redirect(game['download_url'])

@app.route('/search')
def search():
    """Search games"""
    query = request.args.get('q', '')
    if query:
        conn = get_db_connection()
        games = conn.execute(
            'SELECT * FROM games WHERE title LIKE ? OR description LIKE ?',
            (f'%{query}%', f'%{query}%')
        ).fetchall()
        conn.close()
    else:
        games = []
    
    return render_template('search.html', games=games, query=query)

@app.route('/process_payment', methods=['POST'])
@login_required
def process_payment():
    """Process Stripe payment"""
    try:
        data = request.get_json()
        payment_method_id = data.get('payment_method_id')
        total = data.get('total')
        
        # Get cart items
        conn = get_db_connection()
        cart_items = conn.execute('''
            SELECT g.*, c.id as cart_id 
            FROM cart c 
            JOIN games g ON c.game_id = g.id 
            WHERE c.user_id = ?
        ''', (session['user_id'],)).fetchall()
        
        if not cart_items:
            return jsonify({'success': False, 'error': 'Carrello vuoto'})
        
        # Calculate total (verify client total)
        calculated_total = sum(item['price'] for item in cart_items)
        if abs(calculated_total - total) > 0.01:  # Allow for small floating point differences
            return jsonify({'success': False, 'error': 'Totale non valido'})
        
        # Create payment intent with Stripe
        try:
            intent = stripe.PaymentIntent.create(
                amount=int(total * 100),  # Stripe uses cents
                currency='eur',
                payment_method=payment_method_id,
                confirmation_method='manual',
                confirm=True,
                metadata={
                    'user_id': session['user_id'],
                    'user_email': session['user_email']
                }
            )
            
            if intent.status == 'succeeded':
                # Payment successful - add games to user's library
                for item in cart_items:
                    # Check if user already owns the game
                    existing_purchase = conn.execute(
                        'SELECT * FROM purchases WHERE user_id = ? AND game_id = ?',
                        (session['user_id'], item['id'])
                    ).fetchone()
                    
                    if not existing_purchase:
                        conn.execute('''
                            INSERT INTO purchases (user_id, game_id, stripe_payment_id)
                            VALUES (?, ?, ?)
                        ''', (session['user_id'], item['id'], intent.id))
                
                # Clear the cart
                conn.execute('DELETE FROM cart WHERE user_id = ?', (session['user_id'],))
                conn.commit()
                
                return jsonify({'success': True})
            else:
                return jsonify({'success': False, 'error': 'Pagamento non riuscito'})
                
        except stripe.error.CardError as e:
            return jsonify({'success': False, 'error': f'Errore carta: {e.user_message}'})
        except stripe.error.StripeError as e:
            return jsonify({'success': False, 'error': 'Errore del sistema di pagamento'})
        
    except Exception as e:
        return jsonify({'success': False, 'error': 'Errore interno del server'})
    finally:
        if 'conn' in locals():
            conn.close()

# Initialize database when app starts
if __name__ == '__main__':
    init_db()
    
    # Add some sample games if database is empty
    conn = get_db_connection()
    games_count = conn.execute('SELECT COUNT(*) FROM games').fetchone()[0]
    
    if games_count == 0:
        sample_games = [
            {
                'title': 'Pixel Adventure',
                'description': 'A classic 2D platformer with retro graphics and challenging gameplay.',
                'price': 9.99,
                'image_url': 'https://via.placeholder.com/300x400/4a90e2/ffffff?text=Pixel+Adventure',
                'download_url': 'https://example.com/downloads/pixel-adventure.zip',
                'developer': 'Indie Games Studio'
            },
            {
                'title': 'Space Explorer',
                'description': 'Explore the vastness of space in this epic adventure game.',
                'price': 19.99,
                'image_url': 'https://via.placeholder.com/300x400/7ed321/ffffff?text=Space+Explorer',
                'download_url': 'https://example.com/downloads/space-explorer.zip',
                'developer': 'Cosmic Games'
            },
            {
                'title': 'Mystery Mansion',
                'description': 'Solve puzzles and uncover secrets in this thrilling mystery game.',
                'price': 14.99,
                'image_url': 'https://via.placeholder.com/300x400/d0021b/ffffff?text=Mystery+Mansion',
                'download_url': 'https://example.com/downloads/mystery-mansion.zip',
                'developer': 'Puzzle Masters'
            }
        ]
        
        for game in sample_games:
            conn.execute('''
                INSERT INTO games (title, description, price, image_url, download_url, developer)
                VALUES (?, ?, ?, ?, ?, ?)
            ''', (game['title'], game['description'], game['price'], 
                 game['image_url'], game['download_url'], game['developer']))
        
        conn.commit()
    
    conn.close()
    
    app.run(debug=True, host='0.0.0.0', port=5000)