<?php

class MasterController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Controller
	|--------------------------------------------------------------------------
	|
	| TODO: Write comments here.
	|
	*/

	public function home()
	{

		// Get the page requested.
		$page = Input::get('page');
		if (!isset($page))
		{
			$page = 1;
		}

		// Check whether any filters are active.
		$active_filters = Input::get('filter');
		$filter_url = '';

		// If no filters are set, apply the default ones.
		if (!isset($active_filters))
		{
			$active_filters = array();
			$default_filters = Filter::where('is_default', 1)->get();
			foreach ($default_filters as $default_filter)
			{
				array_push($active_filters, $default_filter->categoryName);
			}
		}

		if (count($active_filters))
		{
			// Loop through all active filters and construct the aggregate query.
			$whereraw = array();
			foreach ($active_filters as $active_filter)
			{
				$whereraw[] = 'categoryName = "' . $active_filter . '"';
			}

			// Retrieve the list of selected items.
			$items = Item::whereRaw(implode(' or ', $whereraw))->get();

			// Make a URL to use in links.
			$filter_url = 'filter[]=' . implode('&filter[]=', $active_filters);
		}
		else
		{
			$items = Item::all();
		}

		// Loop through them all to combine the same items.
		$table = array();
		$simple_array = array(); // keep track of which items have already been counted

		foreach ($items as $item)
		{
			if (in_array($item->typeID, $simple_array))
			{
				// This item is already in the table.
				$table[$item->typeID]->qty += $item->qty;
			}
			else
			{
				$table[$item->typeID] = (object) array(
					"qty"				=> $item->qty,
					"typeID"			=> $item->typeID,
					"typeName"			=> $item->typeName,
					"category"			=> $item->categoryName,
					"meta"				=> $item->metaGroupName,
					"profitIndustry"	=> $item->type->profit['profitIndustry'],
					"profitImport"		=> $item->type->profit['profitImport'],
					"profitOrLoss"		=> ($item->type->profit['profitIndustry'] > 0) ? 'profit' : 'loss',
				);
				$simple_array[] = $item->typeID;
			}
		}

		// Sort the list of items by quantity.
		usort($table, function ($a, $b)
		{
			if ($a->qty == $b->qty) {
				return 0;
			}
			return ($a->qty > $b->qty) ? -1 : 1;
		});

		// Load the template to display all the items.
		return View::make('home')
			->with('items', array_slice($table, ($page - 1) * 20, 20))
			->with('page', $page)
			->with('filter_url', $filter_url)
			->with('pages', count($table) / 20)
			->nest('sidebar', 'filters', array(
				'filters'			=> Filter::all()->sortBy('categoryName'),
				'active_filters'	=> $active_filters,
			));

	}

}