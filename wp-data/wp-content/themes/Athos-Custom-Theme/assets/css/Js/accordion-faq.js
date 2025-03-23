document.addEventListener('DOMContentLoaded', function() {
  const accordionItems = document.querySelectorAll('.accordion-item');
  
  accordionItems.forEach(item => {
    const header = item.querySelector('.accordion-header');
    const content = item.querySelector('.accordion-content');
    const toggle = item.querySelector('.accordion-toggle');
    
    header.addEventListener('click', () => {
      // Close all other items
      accordionItems.forEach(otherItem => {
        if (otherItem !== item) {
          otherItem.classList.remove('active');
          otherItem.querySelector('.accordion-content').style.display = 'none';
          const otherToggle = otherItem.querySelector('.accordion-toggle');
          otherToggle.classList.remove('minus');
          otherToggle.classList.add('plus');
        }
      });
      
      // Toggle current item
      const isActive = item.classList.contains('active');
      
      if (isActive) {
        item.classList.remove('active');
        content.style.display = 'none';
        toggle.classList.remove('minus');
        toggle.classList.add('plus');
      } else {
        item.classList.add('active');
        content.style.display = 'block';
        toggle.classList.remove('plus');
        toggle.classList.add('minus');
      }
    });
  });
});