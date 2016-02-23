<?php

namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use AppBundle\Entity\history;



/*
 * Dostepne metody:
 * 
 * index - podstawowa strona
 * 
 * shop - zapis produktu do sesji, dodanie produktu do historii oraz 
 * przekazanie do szablonu tabli z wszystkimi produktami i koszykiem
 * 
 * clear_session - oproznia sesje
 * 
 * set_produkt - zapis wybranego produktu do koszyka (sesji)
 * 
 * history - zapis wybranego produktu do bazy danych (id oraz data)
 * 
 * products - zwraca tablice listy wszystkich produktow z bazy danych
 * 
 */







class DefaultController extends Controller
{
  

    /*
     * Index
     */
    
    
    public function indexAction(Request $request){
        
     
        return $this->render('default/index.html.twig');
        
        
    }
    
    
        
    
    
    
    
    /*
     *   Zadania metody:
     * 
     * - Tworzy sesje jezeli nie istnieje
     * - Sprawdza czy istnieje metoda POST i zmienne
     * - Zwraca 4 tablice :
     *   -- tablice produktow z bazy danych oraz jej rozmiar
     *   -- tablice koszyka oraz jej rozmiar
     *   
     */
    
    public function shopAction(Request $request){
        
        /*
         * Sprawdz czy sesja istnieje jezeli nie utworz nowa
         */
        
        if (\PHP_SESSION_ACTIVE !== session_status()) {
                        $session = new Session(); 
                        $session->start();    }
        else { 
            $session = $request->getSession();      }
        
        /*
         * Sprawdz czy istnieje metoda POST oraz product_id.
         * Nastepnie dodaj do kosza wybrany produkt
         */
        
        if ($request->isMethod('POST')) { 
             if ($request->request->has('product_id')) { 
             $basket = $this->set_product($_POST['product_id'], $session); 
             }
        }
      
         /*
          *  Zwraca tablice ze wszystkimi produktami z bazy danych
          */
        
        $products_list = $this->products();   
        
        /*
         * Wyznacz rozmiar listy wszystkich produktow  pobranych z bazy
         */
        
        $size_products_list = \count($products_list) - 1; //
        
        /*
         * Pobierz liste produktow z koszyka(sesji)
         */
        
        $basket  = $session->get('basket');
        
        return $this->render('default/index2.html.twig',
                array(
                    'size_products_list' => $size_products_list,
                    'products_list' => $products_list,
                    'size_basket' => count($basket)-1,
                    'basket' => $basket,
                ));
        
    }
    
    
    
    
    
    
    
    
    /*
     * Metoda oproznia kosz - czysci sesje
     */
    
    public function clear_sessionAction(Request $request){
        
        
       $session = $request->getSession(); 
       $session->clear();
        return $this->render('default/index.html.twig');
    }
    
    
    
    
    
    
    
    
    /*
     * Metoda pobiera wybrany produkt wedlug ID 
     * nastepnie dodaje do istniejacej sesji wybrany produkt przez uzytkownika
     */

    public function set_product($id, $session){
        
        /*
         *  Pobierz dane produktu z bazy danych wedlug wyznaczonego ID
         */
        
        $base = $this->getDoctrine()->getManager();
        $query = $base->createQuery(
        'SELECT p
        FROM AppBundle:Products p
        WHERE p.id = :id'
        )->setParameter('id', $id);
        
        /*
         * Wyslij do metody history, Id ktore zostanie nastepnie zapisane w bazie danych wraz z data
         */
        
        $this->history($id);
        
        /*
         * Konwersja zapytania do tablicy
         */
        
        $product = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        /*
         * Tworzy tablice z produktem wybranym przez uzytkownika
         */
        
        $new_product =  array(
                'name' => $product[0]['name'],
                'price' => $product[0]['price'],
                'description' => $product[0]['description'],);
      
        /*
         * Pobierz produkty z sessji oraz dodaj nowy produkt do koszyka
         */
        
        $basket = $session->get('basket');
        $basket[] = $new_product;
        $session->set('basket', $basket );
  
       return \NULL;
    }
    
 
    
    
    
    
    
    
    /*
     * Metoda zapisuje do tabeli "history" bazy danych 
     * Id produktu oraz czas dodania produktu do koszyka
     */
 
    public function history($id){
        
    $product = new history();
    $product->setIdProduct($id);
    $product->setDate(new \DateTime);
  
    $data = $this->getDoctrine()->getManager();

    $data->persist($product);
    $data->flush();      
        
    return \NULL;
    }
    
    
    
    
    
    
    
    /*
     * Metoda przekazujaca wszystkie produkty z bazy danych "sklep"
     */
    
    public function products(){
        
        $base= $this->getDoctrine()->getManager();
        
        $query = $base->createQuery( 
        'SELECT p 
        FROM AppBundle:Products p ORDER BY p.id ASC');

        $products = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        
        return $products;
    }
    

    
    
}
